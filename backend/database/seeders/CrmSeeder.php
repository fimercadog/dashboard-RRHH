<?php

namespace Database\Seeders;

use App\Models\Activity;
use App\Models\Client;
use App\Models\ClientNote;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Deal;
use App\Models\Segment;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class CrmSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::first();
        $admin   = User::where('email', 'admin@andespeople.co')->first();

        if (! $company || ! $admin) {
            return;
        }

        // ── Segmentos ─────────────────────────────────────────────────────────
        $segments = collect([
            ['name' => 'Clientes activos',   'description' => 'Empresas con relacion comercial vigente'],
            ['name' => 'Potenciales grandes', 'description' => 'Oportunidades con valor > 50M'],
            ['name' => 'PYME colombiana',     'description' => 'Empresas medianas sector servicios'],
        ])->map(fn ($d) => Segment::firstOrCreate(
            ['company_id' => $company->id, 'name' => $d['name']],
            ['description' => $d['description']]
        ));

        // ── Clientes ─────────────────────────────────────────────────────────
        $clientData = [
            ['company_name' => 'Grupo Empresarial Andino S.A.S', 'first_name' => 'Ricardo',  'last_name' => 'Pedraza',  'email' => 'rpedraza@grupoandino.co',    'phone' => '+57 601 312 4400', 'city' => 'Bogota',      'type' => 'NIT', 'num' => '900.123.456-7'],
            ['company_name' => 'Constructora del Pacifico Ltda',  'first_name' => 'Lorena',   'last_name' => 'Castillo', 'email' => 'lcastillo@constpacifico.co', 'phone' => '+57 602 455 0088', 'city' => 'Cali',        'type' => 'NIT', 'num' => '800.456.789-2'],
            ['company_name' => null,                               'first_name' => 'Hernan',   'last_name' => 'Giraldo',  'email' => 'hgiraldo@outlook.com',       'phone' => '+57 310 812 5566', 'city' => 'Medellin',    'type' => 'CC',  'num' => '1035012345'],
            ['company_name' => 'Logistica Caribe S.A.S',          'first_name' => 'Patricia', 'last_name' => 'Orozco',   'email' => 'porozco@logcaribe.co',       'phone' => '+57 605 700 1122', 'city' => 'Barranquilla','type' => 'NIT', 'num' => '901.234.567-1'],
            ['company_name' => 'Soluciones TIC del Sur',          'first_name' => 'Mauricio', 'last_name' => 'Alvarado', 'email' => 'malvarado@ticdsur.co',       'phone' => '+57 608 900 3344', 'city' => 'Pasto',       'type' => 'NIT', 'num' => '900.987.654-3'],
        ];

        $clients = collect($clientData)->map(fn ($d) => Client::firstOrCreate(
            ['company_id' => $company->id, 'identification_number' => $d['num']],
            [
                'identification_type' => $d['type'],
                'company_name'        => $d['company_name'],
                'first_name'          => $d['first_name'],
                'last_name'           => $d['last_name'],
                'email'               => $d['email'],
                'phone'               => $d['phone'],
                'city'                => $d['city'],
                'status'              => 'active',
                'client_uuid'         => Str::uuid(),
            ]
        ));

        // Asociar clientes a segmentos
        $clients->take(3)->each(fn ($c) => $segments[0]->clients()->syncWithoutDetaching([$c->id]));
        $clients->take(2)->each(fn ($c) => $segments[1]->clients()->syncWithoutDetaching([$c->id]));
        $clients->skip(2)->each(fn ($c) => $segments[2]->clients()->syncWithoutDetaching([$c->id]));

        // ── Contactos ─────────────────────────────────────────────────────────
        $contactData = [
            [$clients[0]->id, 'Jorge Suarez',    'Gerente Financiero',  'jsuarez@grupoandino.co',     '+57 310 555 0001'],
            [$clients[0]->id, 'Monica Patino',   'Directora Compras',   'mpatino@grupoandino.co',     '+57 310 555 0002'],
            [$clients[1]->id, 'Rafael Bermudez', 'Jefe de Proyectos',   'rbermudez@constpacifico.co', '+57 315 612 0011'],
            [$clients[3]->id, 'Claudia Velez',   'Gerente Operaciones', 'cvelez@logcaribe.co',        '+57 320 700 0099'],
            [$clients[4]->id, 'Juan Alvarado',   'CTO',                 'jalvarado@ticdsur.co',       '+57 318 900 1122'],
        ];

        foreach ($contactData as [$clientId, $name, $job, $email, $phone]) {
            Contact::firstOrCreate(
                ['company_id' => $company->id, 'email' => $email],
                ['client_id' => $clientId, 'name' => $name, 'job_title' => $job, 'phone' => $phone]
            );
        }

        // ── Notas de cliente ─────────────────────────────────────────────────
        $noteData = [
            [$clients[0]->id, 'Requieren factura electronica obligatoria desde el primer mes.'],
            [$clients[1]->id, 'Proceso de compras aprobado por comite; demora minima 15 dias.'],
            [$clients[3]->id, 'Potencial contrato anual. Priorizar seguimiento mensual.'],
        ];

        foreach ($noteData as [$clientId, $body]) {
            ClientNote::firstOrCreate(
                ['company_id' => $company->id, 'client_id' => $clientId, 'body' => $body],
                ['user_id' => $admin->id]
            );
        }

        // ── Deals (oportunidades) ─────────────────────────────────────────────
        $dealData = [
            [$clients[0]->id, 'Implementacion RRHH Grupo Andino',       85_000_000, 'proposal',      20],
            [$clients[1]->id, 'Consultoria gestion proyectos Pacifico', 42_000_000, 'qualification', 45],
            [$clients[2]->id, 'Licencia plataforma freelancer',          8_500_000, 'won',           -30],
            [$clients[3]->id, 'Suite logistica Caribe',                130_000_000, 'negotiation',   15],
            [$clients[4]->id, 'Soporte TIC y capacitacion',             22_000_000, 'prospecting',   60],
            [$clients[0]->id, 'Modulo nomina y biometrico',             38_000_000, 'proposal',      25],
            [$clients[1]->id, 'ERP Construccion fase 2',                95_000_000, 'stalled',       90],
            [$clients[3]->id, 'Contrato mantenimiento anual',           18_000_000, 'lost',         -45],
        ];

        $deals = collect($dealData)->map(fn ($d) => Deal::firstOrCreate(
            ['company_id' => $company->id, 'client_id' => $d[0], 'title' => $d[1]],
            [
                'owner_id'            => $admin->id,
                'amount'              => $d[2],
                'stage'               => $d[3],
                'expected_close_date' => Carbon::today()->addDays($d[4]),
                'notes'               => 'Oportunidad registrada por el sistema demo.',
            ]
        ));

        // ── Actividades ───────────────────────────────────────────────────────
        $activityData = [
            [$deals[0]->id, $clients[0]->id, 'call',    'Llamada inicial de descubrimiento',   'done',    -5],
            [$deals[0]->id, $clients[0]->id, 'email',   'Envio de propuesta economica',         'done',    -3],
            [$deals[0]->id, $clients[0]->id, 'meeting', 'Reunion de presentacion de modulos',  'planned',  7],
            [$deals[1]->id, $clients[1]->id, 'call',    'Calificacion de necesidades',          'done',    -8],
            [$deals[3]->id, $clients[3]->id, 'meeting', 'Demostracion del sistema logistico',  'done',    -2],
            [$deals[3]->id, $clients[3]->id, 'call',    'Seguimiento propuesta ejecutiva',      'planned',  3],
            [$deals[5]->id, $clients[0]->id, 'task',    'Preparar demo modulo nomina',          'planned',  5],
            [$deals[2]->id, $clients[2]->id, 'email',   'Confirmacion de cierre y onboarding', 'done',   -28],
        ];

        foreach ($activityData as [$dealId, $clientId, $type, $title, $status, $days]) {
            Activity::firstOrCreate(
                ['company_id' => $company->id, 'deal_id' => $dealId, 'title' => $title],
                [
                    'client_id' => $clientId,
                    'user_id'   => $admin->id,
                    'type'      => $type,
                    'status'    => $status,
                    'due_at'    => Carbon::today()->addDays($days)->startOfDay(),
                    'done_at'   => $status === 'done' ? Carbon::today()->addDays($days)->startOfDay() : null,
                    'body'      => 'Actividad generada por el sistema demo.',
                ]
            );
        }
    }
}
