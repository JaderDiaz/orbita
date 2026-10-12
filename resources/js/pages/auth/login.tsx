import { Form, Head } from '@inertiajs/react';
import { useState } from 'react';
import InputError from '@/components/input-error';
import PasswordInput from '@/components/password-input';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { store } from '@/routes/login';

/** Matches config('orbita.demo_password'); only reachable in debug mode. */
const DEMO_PASSWORD = 'orbita2026';

type DemoAccount = {
    email: string;
    role: string;
};

type Props = {
    status?: string;
    demoAccounts?: DemoAccount[];
};

export default function Login({ status, demoAccounts = [] }: Props) {
    const [email, setEmail] = useState('');
    const [password, setPassword] = useState('');
    return (
        <>
            <Head title="Iniciar sesión" />

            <Form
                {...store.form()}
                resetOnSuccess={['password']}
                className="flex flex-col gap-6"
            >
                {({ processing, errors }) => (
                    <>
                        <div className="grid gap-6">
                            <div className="grid gap-2">
                                <Label htmlFor="email">
                                    Correo electrónico
                                </Label>
                                <Input
                                    id="email"
                                    type="email"
                                    name="email"
                                    value={email}
                                    onChange={(event) =>
                                        setEmail(event.target.value)
                                    }
                                    required
                                    autoFocus
                                    tabIndex={1}
                                    autoComplete="email"
                                    placeholder="nombre@agencia.co"
                                />
                                <InputError message={errors.email} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="password">Contraseña</Label>
                                <PasswordInput
                                    id="password"
                                    name="password"
                                    value={password}
                                    onChange={(event) =>
                                        setPassword(event.target.value)
                                    }
                                    required
                                    tabIndex={2}
                                    autoComplete="current-password"
                                    placeholder="••••••••"
                                />
                                <InputError message={errors.password} />
                            </div>

                            <div className="flex items-center space-x-3">
                                <Checkbox
                                    id="remember"
                                    name="remember"
                                    tabIndex={3}
                                />
                                <Label htmlFor="remember">Recordarme</Label>
                            </div>

                            <Button
                                type="submit"
                                className="mt-4 w-full"
                                tabIndex={4}
                                disabled={processing}
                                data-test="login-button"
                            >
                                {processing && <Spinner />}
                                Ingresar
                            </Button>
                        </div>
                    </>
                )}
            </Form>

            {status && (
                <div className="mt-4 text-center text-[12.5px] font-medium text-[#1a7f37]">
                    {status}
                </div>
            )}

            {demoAccounts.length > 0 && (
                <div className="mt-6 flex flex-col gap-2">
                    <span className="text-[10.5px] tracking-[0.08em] text-text-tertiary uppercase">
                        Cuentas de prueba
                    </span>

                    {demoAccounts.map((account) => (
                        <button
                            key={account.email}
                            type="button"
                            onClick={() => {
                                setEmail(account.email);
                                setPassword(DEMO_PASSWORD);
                            }}
                            className="flex items-center justify-between gap-3 rounded-lg border border-dashed border-[#e5e5ea] px-3 py-2.5 text-[12.5px] transition-colors hover:border-[#d1d1d6] hover:bg-card"
                        >
                            <span className="font-mono text-text-secondary">
                                {account.email}
                            </span>
                            <span className="text-text-tertiary">
                                {account.role}
                            </span>
                        </button>
                    ))}
                </div>
            )}
        </>
    );
}

Login.layout = {
    title: 'Inicia sesión en Orbita',
    description: 'Usa la cuenta que te asignó tu agencia.',
};
