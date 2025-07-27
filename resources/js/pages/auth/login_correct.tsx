
import AuthLayout from '@/layouts/auth-layout';
import { Head,  } from '@inertiajs/react';


export default function LoginCorrect() {

    return (
        <AuthLayout title="Iniciando Sesión" description="Sesión Iniciada Correctamente">
            <Head title="Iniciando Sesión" />

        </AuthLayout>
    );
}
