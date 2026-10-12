<?php

use App\Enums\TipoPersona;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clientes', function (Blueprint $table) {
            $table->id();
            $table->string('tipo_persona', 10);

            // Catalog values, never stamped strings: the agency renames these
            // in Maestros and a rename must not rewrite any client.
            $table->foreignId('tipo_documento_value_id')->constrained('catalog_values')->restrictOnDelete();
            $table->foreignId('regimen_value_id')->constrained('catalog_values')->restrictOnDelete();

            $table->string('documento', 30);

            // Only a NIT carries one. Stored rather than derived because it is
            // part of the identity frozen onto an issued document.
            $table->char('dv', 1)->nullable();

            $table->string('nombre', 180);

            // Prefix of this client's COT- and FAC- series. Three characters,
            // uppercase, unique across the install.
            $table->char('tag', 3);

            $table->string('ciudad', 120);

            // The prototype has no departamento. ReteICA is a municipal tax, so
            // the location stops being decorative once invoicing lands.
            $table->string('departamento', 120);

            $table->string('direccion', 255)->nullable();
            $table->text('notas')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('is_active');
            $table->index('nombre');
        });

        Schema::create('cliente_contactos', function (Blueprint $table) {
            $table->id();

            // Contacts are part of the client, not history others consult: a
            // cotización freezes the contact's details when it is issued rather
            // than pointing at this row. They go with the client.
            $table->foreignId('cliente_id')->constrained('clientes')->cascadeOnDelete();

            $table->string('nombre', 150);
            $table->string('cargo', 120)->nullable();
            $table->string('email', 180);
            $table->string('telefono', 40)->nullable();
            $table->boolean('is_principal')->default(false);
            $table->integer('position')->default(0);
            $table->timestamps();

            $table->index(['cliente_id', 'position']);
        });

        $this->addClienteInvariants();
        $this->addContactoInvariants();
        $this->addSearchIndexes();
    }

    /**
     * Identity and numbering invariants.
     *
     * The tag is the prefix of this client's invoice and quote series, so a
     * duplicate would let two documents be issued with the same number. That is
     * a fiscal problem, not a data-quality one, and it belongs in the database.
     */
    private function addClienteInvariants(): void
    {
        $tipos = implode(', ', array_map(
            fn (string $tipo): string => "'{$tipo}'",
            TipoPersona::values(),
        ));

        DB::statement("
            ALTER TABLE clientes
            ADD CONSTRAINT clientes_tipo_persona_check
            CHECK (tipo_persona IN ({$tipos}))
        ");

        DB::statement("
            ALTER TABLE clientes
            ADD CONSTRAINT clientes_tag_format_check
            CHECK (tag ~ '^[A-Z0-9]{3}\$')
        ");

        // One person or company is one client, whichever document identifies
        // them. Case-insensitive because a cédula de extranjería may carry
        // letters.
        DB::statement('
            CREATE UNIQUE INDEX clientes_documento_index
            ON clientes (lower(documento))
        ');

        DB::statement('
            CREATE UNIQUE INDEX clientes_tag_index
            ON clientes (upper(tag))
        ');
    }

    /**
     * Every client has exactly one principal contact.
     *
     * The partial unique index stops a SECOND principal, but "at least one"
     * cannot be expressed as an index at all: it is a statement about rows that
     * are absent. A deferred constraint trigger evaluated at commit is the same
     * mechanism the standard prescribes for `sum(fases.pct) = 100`, and it
     * makes the rule survive every write path rather than just the form.
     */
    private function addContactoInvariants(): void
    {
        DB::statement('
            CREATE UNIQUE INDEX cliente_contactos_single_principal_index
            ON cliente_contactos (cliente_id)
            WHERE is_principal
        ');

        DB::statement('
            CREATE UNIQUE INDEX cliente_contactos_email_index
            ON cliente_contactos (cliente_id, lower(email))
        ');

        DB::unprepared("
            CREATE OR REPLACE FUNCTION clientes_assert_single_principal()
            RETURNS trigger
            LANGUAGE plpgsql
            AS \$\$
            DECLARE
                targets bigint[];
                target bigint;
                principals integer;
            BEGIN
                -- Branches are IF rather than a CASE expression because
                -- PL/pgSQL compiles a CASE into one SQL expression and would
                -- resolve NEW.cliente_id even on the clientes record, which has
                -- no such field. DELETE is split out because NEW is unassigned
                -- there, and an UPDATE that moves a contact between clients has
                -- to leave BOTH of them valid.
                IF TG_TABLE_NAME = 'clientes' THEN
                    targets := ARRAY[NEW.id];
                ELSIF TG_OP = 'DELETE' THEN
                    targets := ARRAY[OLD.cliente_id];
                ELSIF TG_OP = 'UPDATE' THEN
                    targets := ARRAY[OLD.cliente_id, NEW.cliente_id];
                ELSE
                    targets := ARRAY[NEW.cliente_id];
                END IF;

                FOREACH target IN ARRAY targets LOOP
                    -- The client may be gone: deleting one cascades to its
                    -- contacts, and this trigger fires for each of them.
                    IF NOT EXISTS (SELECT 1 FROM clientes WHERE id = target) THEN
                        CONTINUE;
                    END IF;

                    SELECT count(*) INTO principals
                    FROM cliente_contactos
                    WHERE cliente_id = target AND is_principal;

                    IF principals <> 1 THEN
                        RAISE EXCEPTION
                            'El cliente % debe tener exactamente un contacto principal, tiene %',
                            target, principals
                            USING ERRCODE = 'check_violation';
                    END IF;
                END LOOP;

                RETURN NULL;
            END;
            \$\$
        ");

        DB::statement('
            CREATE CONSTRAINT TRIGGER clientes_single_principal_trigger
            AFTER INSERT ON clientes
            DEFERRABLE INITIALLY DEFERRED
            FOR EACH ROW EXECUTE FUNCTION clientes_assert_single_principal()
        ');

        DB::statement('
            CREATE CONSTRAINT TRIGGER cliente_contactos_single_principal_trigger
            AFTER INSERT OR UPDATE OR DELETE ON cliente_contactos
            DEFERRABLE INITIALLY DEFERRED
            FOR EACH ROW EXECUTE FUNCTION clientes_assert_single_principal()
        ');
    }

    /**
     * Trigram indexes for the list's search box.
     *
     * Wrapped in orbita_unaccent so "Medellin" finds "Medellín". The standard
     * requires the search to run on the server, and without these it would
     * sequential-scan the table on every keystroke.
     */
    private function addSearchIndexes(): void
    {
        DB::statement('
            CREATE INDEX clientes_nombre_trgm_index
            ON clientes USING gin (orbita_unaccent(nombre) gin_trgm_ops)
        ');

        DB::statement('
            CREATE INDEX clientes_documento_trgm_index
            ON clientes USING gin (documento gin_trgm_ops)
        ');

        DB::statement('
            CREATE INDEX cliente_contactos_nombre_trgm_index
            ON cliente_contactos USING gin (orbita_unaccent(nombre) gin_trgm_ops)
        ');
    }

    public function down(): void
    {
        DB::statement('DROP TRIGGER IF EXISTS cliente_contactos_single_principal_trigger ON cliente_contactos');
        DB::statement('DROP TRIGGER IF EXISTS clientes_single_principal_trigger ON clientes');
        DB::statement('DROP FUNCTION IF EXISTS clientes_assert_single_principal()');

        Schema::dropIfExists('cliente_contactos');
        Schema::dropIfExists('clientes');
    }
};
