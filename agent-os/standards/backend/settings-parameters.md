# Parámetros (settings)

**The schema is code; only overrides are data.** Parameters change by deploy, their values don't.

```php
// config/parametros.php
'security.password_min_length' => [
    'module' => 'seguridad',
    'label' => 'Longitud mínima de contraseña',
    'type' => 'num',            // text|num|money|toggle|select|seg|area
    'default' => 10,
    'suffix' => 'caracteres',
    'rules' => ['integer', 'min:8', 'max:64'],
    'consumed_by' => ['Login', 'Perfil'],
],
```

```sql
setting_values(key citext primary key, value jsonb, updated_by, updated_at)
```

- Typed access only: `Param::int(ParamKey::PasswordMinLength)`, `Param::money(...)`,
  `Param::bool(...)`. Casting follows the declared `type` — `money` returns a `Money`.
- One `Cache::rememberForever('settings:all')` hydrated per request, flushed on save. Never a
  query per key.
- Dirty state ("N cambios sin guardar") is client-side React state. The save posts only changed
  keys; the server validates each against its declared `rules` in one transaction and writes one
  audit entry per key.
- **A security parameter must actually be consumed.** Inactivity timeout as middleware,
  invitation expiry as the signed-URL TTL, 2FA requirement as role-keyed middleware. A parameter
  nobody reads is a lie in the UI.
