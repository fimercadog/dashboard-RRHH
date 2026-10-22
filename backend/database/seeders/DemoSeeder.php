<?php

namespace Database\Seeders;

use App\Models\Attendance;
use App\Models\AuditLog;
use App\Models\Company;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeDocument;
use App\Models\PermissionRequest;
use App\Models\Position;
use App\Models\Shift;
use App\Models\ShiftAssignment;
use App\Models\SickLeave;
use App\Models\User;
use App\Models\VacationRequest;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        // ── 1. Empresa demo ───────────────────────────────────────────────────
        $demoCompany = Company::firstOrCreate(
            ['name' => 'Empresa Demo SAS'],
            [
                'nit'      => '900.999.888-1',
                'email'    => 'demo@empresademo.co',
                'phone'    => '+57 601 999 0001',
                'address'  => 'Carrera 7 #71-21, Bogota',
                'timezone' => 'America/Bogota',
                'locale'   => 'es',
            ]
        );

        // ── 1b. Roles de la empresa demo (cada empresa tiene sus propios roles) ──
        $allPermissions = Permission::pluck('name')->all();
        $adminPermissions = array_values(array_filter(
            $allPermissions,
            fn ($p) => ! in_array($p, ['whatsapp.manage', 'ai.manage', 'integrations.view'])
        ));

        $demoRoles = [
            'Super Admin'             => $allPermissions,
            'Administrador de empresa'=> $adminPermissions,
            'Recursos Humanos'        => ['dashboard.view', 'employees.manage', 'attendance.manage', 'requests.approve', 'documents.manage', 'reports.view', 'leads.view'],
            'Supervisor'              => ['dashboard.view', 'attendance.manage', 'requests.approve', 'reports.view'],
            'Empleado'                => ['dashboard.view'],
        ];

        foreach ($demoRoles as $roleName => $rolePermissions) {
            Role::firstOrCreate(
                ['name' => $roleName, 'guard_name' => 'web', 'company_id' => $demoCompany->id]
            )->syncPermissions(array_filter($rolePermissions, fn ($p) => in_array($p, $allPermissions)));
        }

        // ── 2. Usuarios demo ──────────────────────────────────────────────────
        $password = config('app.demo_password', 'DFC-Demo-2026!');

        // Los empleados se crean más adelante; guardamos índices para vinculación
        $demoEmployees = collect(); // se llena en el bloque de RRHH

        $demoAdmin = User::updateOrCreate(
            ['email' => 'admin.demo@empresademo.co'],
            [
                'company_id'  => $demoCompany->id,
                'employee_id' => null,
                'name'        => 'Admin Demo',
                'password'    => Hash::make($password),
                'status'      => 'active',
            ]
        );
        $demoAdmin->syncRoles([Role::where('name', 'Administrador de empresa')->where('company_id', $demoCompany->id)->first()]);

        // Usuarios con rol dependiente de empleado (se vinculan tras crear empleados)
        $pendingUsers = [
            ['email' => 'rrhh.demo@empresademo.co',       'name' => 'RRHH Demo',       'role' => 'Recursos Humanos', 'emp_index' => 1],
            ['email' => 'supervisor.demo@empresademo.co',  'name' => 'Supervisor Demo',  'role' => 'Supervisor',       'emp_index' => 2],
            ['email' => 'empleado.demo@empresademo.co',    'name' => 'Empleado Demo',    'role' => 'Empleado',         'emp_index' => 4],
        ];

        // ── 3. RRHH inline ────────────────────────────────────────────────────

        // Departamentos
        $departments = collect([
            ['name' => 'Talento Humano', 'description' => 'Gestion integral del ciclo de vida del colaborador'],
            ['name' => 'Operaciones',    'description' => 'Ejecucion y seguimiento operativo'],
            ['name' => 'Finanzas',       'description' => 'Control financiero y administrativo'],
            ['name' => 'Tecnologia',     'description' => 'Producto, datos y soporte interno'],
        ])->map(fn ($data) => Department::firstOrCreate(
            ['company_id' => $demoCompany->id, 'name' => $data['name']],
            $data + ['status' => 'active']
        ));

        // Posiciones
        $positions = collect([
            ['name' => 'Gerente de Talento',          'department_id' => $departments[0]->id],
            ['name' => 'Analista de Nomina',           'department_id' => $departments[0]->id],
            ['name' => 'Coordinador de Operaciones',   'department_id' => $departments[1]->id],
            ['name' => 'Asistente Operativo',          'department_id' => $departments[1]->id],
            ['name' => 'Controller Financiero',        'department_id' => $departments[2]->id],
            ['name' => 'Analista Contable',            'department_id' => $departments[2]->id],
            ['name' => 'Ingeniero Full Stack',         'department_id' => $departments[3]->id],
            ['name' => 'Soporte TI',                   'department_id' => $departments[3]->id],
        ])->map(fn ($data) => Position::firstOrCreate(
            ['company_id' => $demoCompany->id, 'name' => $data['name']],
            $data + ['description' => 'Cargo base del catalogo organizacional', 'status' => 'active']
        ));

        // 20 empleados ficticios colombianos (mismos nombres, emails @empresademo.co)
        $names = [
            ['Camila', 'Rojas'], ['Sebastian', 'Moreno'], ['Valentina', 'Castro'], ['Mateo', 'Herrera'],
            ['Laura', 'Medina'], ['Daniel', 'Gomez'], ['Isabella', 'Torres'], ['Nicolas', 'Vargas'],
            ['Paula', 'Jimenez'], ['Andres', 'Ramirez'], ['Manuela', 'Suarez'], ['Felipe', 'Cortes'],
            ['Natalia', 'Ortega'], ['Santiago', 'Reyes'], ['Carolina', 'Mendoza'], ['Julian', 'Pardo'],
            ['Diana', 'Navarro'], ['Tomas', 'Salazar'], ['Mariana', 'Acosta'], ['Esteban', 'Mejia'],
        ];

        $demoEmployees = collect($names)->map(function ($name, $index) use ($demoCompany, $positions) {
            $position = $positions[$index % $positions->count()];

            return Employee::firstOrCreate([
                'company_id'    => $demoCompany->id,
                'employee_code' => 'DEMO-'.str_pad((string) ($index + 1), 4, '0', STR_PAD_LEFT),
            ], [
                'first_name'          => $name[0],
                'last_name'           => $name[1],
                'identification_type' => 'CC',
                'identification_number' => '20'.str_pad((string) (8000000 + $index * 1731), 8, '0', STR_PAD_LEFT),
                'email'               => strtolower($name[0].'.'.$name[1]).'@empresademo.co',
                'phone'               => '+57 301 '.rand(100, 999).' '.rand(1000, 9999),
                'birth_date'          => Carbon::today()->subYears(rand(24, 48))->subDays(rand(0, 340)),
                'city'                => 'Bogota',
                'hire_date'           => Carbon::today()->subMonths(rand(2, 60)),
                'employment_status'   => $index === 18 ? 'on_leave' : 'active',
                'department_id'       => $position->department_id,
                'position_id'         => $position->id,
                'contract_type'       => ['Indefinido', 'Fijo', 'Prestacion de servicios'][$index % 3],
                'salary'              => 2400000 + ($index * 185000),
                'work_schedule'       => 'Lunes a viernes 08:00-17:00',
            ]);
        });

        $departments->each(fn ($department, $index) => $department->update(['manager_id' => $demoEmployees[$index]->id]));

        // Vincular usuarios con rol a sus empleados
        foreach ($pendingUsers as $userData) {
            $emp = $demoEmployees[$userData['emp_index']];
            $user = User::updateOrCreate(
                ['email' => $userData['email']],
                [
                    'company_id'  => $demoCompany->id,
                    'employee_id' => $emp->id,
                    'name'        => $userData['name'],
                    'password'    => Hash::make($password),
                    'status'      => 'active',
                ]
            );
            $user->syncRoles([Role::where('name', $userData['role'])->where('company_id', $demoCompany->id)->first()]);
        }

        // Asistencias: seed para 365 dias habiles (idempotente: elimina solo 'seed')
        Attendance::where('company_id', $demoCompany->id)->where('source', 'seed')->delete();
        $now = Carbon::now();
        $attendanceRows = [];
        for ($day = 0; $day < 365; $day++) {
            $date = Carbon::today()->subDays($day);
            if ($date->isWeekend()) {
                continue;
            }
            $presentCut = 13 + ($date->month + $date->weekOfYear) % 5;
            foreach ($demoEmployees as $index => $employee) {
                $seed   = ($index * 7 + $day * 3) % 20;
                $status = $seed < $presentCut ? 'present' : ($seed < $presentCut + 3 ? 'late' : 'absent');
                $late   = $status === 'late' ? 10 + ($seed % 4) * 6 : 0;
                $attendanceRows[] = [
                    'company_id'   => $demoCompany->id,
                    'employee_id'  => $employee->id,
                    'date'         => $date->toDateString(),
                    'status'       => $status,
                    'check_in'     => $status === 'absent' ? null : sprintf('08:%02d', $late ? ($late % 60) : (($index + $day) % 5) * 3),
                    'check_out'    => $status === 'absent' ? null : '17:00',
                    'late_minutes' => $late,
                    'source'       => 'seed',
                    'created_at'   => $now,
                    'updated_at'   => $now,
                ];
            }
        }
        foreach (array_chunk($attendanceRows, 500) as $chunk) {
            Attendance::insert($chunk);
        }

        // 7 solicitudes de vacaciones
        $vacations = [
            [4,  12,  18, 5, 'pending',  'Vacaciones familiares programadas',         null],
            [2,   5,   9, 3, 'pending',  'Dias pendientes del periodo anterior',      null],
            [11, 25,  32, 6, 'pending',  'Vacaciones de fin de ano',                  null],
            [7, -30, -24, 5, 'approved', 'Descanso anual',           'Aprobada por Talento Humano'],
            [9, -62, -58, 4, 'approved', 'Viaje personal',           'Aprobada, cubre el cargo un backup'],
            [15, 40,  45, 4, 'approved', 'Licencia de matrimonio',   'Aprobada segun politica interna'],
            [13, -12, -10, 2, 'rejected', 'Solicitud con menos de 15 dias de anticipacion', null],
        ];
        foreach ($vacations as [$emp, $from, $to, $days, $status, $reason, $note]) {
            VacationRequest::firstOrCreate(
                ['company_id' => $demoCompany->id, 'employee_id' => $demoEmployees[$emp]->id, 'start_date' => Carbon::today()->addDays($from)],
                [
                    'end_date'         => Carbon::today()->addDays($to),
                    'requested_days'   => $days,
                    'reason'           => $reason,
                    'status'           => $status,
                    'approved_by'      => $status === 'pending' ? null : $demoAdmin->id,
                    'approved_at'      => $status === 'approved' ? Carbon::today()->addDays($from)->subDays(3) : null,
                    'rejection_reason' => $status === 'rejected' ? 'No cumple la anticipacion minima requerida.' : null,
                ],
            );
        }

        // 6 permisos
        $perms = [
            [6,   3,   3, 1, 'medical',      'Cita medica especializada',           'pending'],
            [3,   1,   1, 1, 'personal',     'Tramite bancario personal',           'pending'],
            [10, -5,  -5, 1, 'family',       'Diligencia escolar de un hijo',       'approved'],
            [14, -20, -19, 2, 'study',       'Presentacion de examen universitario','approved'],
            [17,  7,   7, 1, 'personal',     'Cita notarial',                       'pending'],
            [8,  -35, -34, 2, 'bereavement', 'Luto por familiar de segundo grado',  'approved'],
        ];
        foreach ($perms as [$emp, $from, $to, $days, $type, $reason, $status]) {
            PermissionRequest::firstOrCreate(
                ['company_id' => $demoCompany->id, 'employee_id' => $demoEmployees[$emp]->id, 'start_date' => Carbon::today()->addDays($from)],
                [
                    'type'         => $type,
                    'end_date'     => Carbon::today()->addDays($to),
                    'requested_days' => $days,
                    'reason'       => $reason,
                    'status'       => $status,
                    'approved_by'  => $status === 'approved' ? $demoAdmin->id : null,
                    'approved_at'  => $status === 'approved' ? Carbon::today()->addDays($from)->subDays(2) : null,
                ],
            );
        }

        // 5 incapacidades
        $sickLeaves = [
            [18, -2,  3,  6, 'Enfermedad general',      'Incapacidad certificada por EPS',  'active'],
            [5,  -1,  1,  2, 'Enfermedad general',      'Cuadro viral, reposo domiciliario', 'active'],
            [12, -25, -20, 6, 'Accidente laboral',      'Esguince atendido por ARL',        'closed'],
            [3,  -70, -40, 30, 'Licencia de maternidad','Licencia legal de maternidad',     'closed'],
            [9,  -14, -12, 3, 'Enfermedad general',     'Procedimiento ambulatorio',        'closed'],
        ];
        foreach ($sickLeaves as [$emp, $from, $to, $days, $type, $description, $status]) {
            SickLeave::firstOrCreate(
                ['company_id' => $demoCompany->id, 'employee_id' => $demoEmployees[$emp]->id, 'start_date' => Carbon::today()->addDays($from)],
                [
                    'end_date'    => Carbon::today()->addDays($to),
                    'days'        => $days,
                    'type'        => $type,
                    'description' => $description,
                    'status'      => $status,
                ],
            );
        }

        // 8 documentos de empleado
        foreach ($demoEmployees->take(8) as $index => $employee) {
            EmployeeDocument::firstOrCreate(
                ['company_id' => $demoCompany->id, 'employee_id' => $employee->id, 'name' => 'Contrato laboral'],
                [
                    'document_type'   => 'contract',
                    'file_path'       => 'documents/demo/contrato-'.$employee->employee_code.'.pdf',
                    'issue_date'      => $employee->hire_date,
                    'expiration_date' => Carbon::today()->addDays(20 + $index * 9),
                    'status'          => $index < 3 ? 'expiring' : 'valid',
                ]
            );
        }

        // 5 turnos
        $shifts = collect([
            ['name' => 'Administrativo',  'start_time' => '08:00', 'end_time' => '17:00', 'break_minutes' => 60],
            ['name' => 'Turno Manana',    'start_time' => '06:00', 'end_time' => '14:00', 'break_minutes' => 30],
            ['name' => 'Turno Tarde',     'start_time' => '14:00', 'end_time' => '22:00', 'break_minutes' => 30],
            ['name' => 'Turno Noche',     'start_time' => '22:00', 'end_time' => '06:00', 'break_minutes' => 45],
            ['name' => 'Fin de semana',   'start_time' => '08:00', 'end_time' => '16:00', 'break_minutes' => 30],
        ])->map(fn ($data) => Shift::firstOrCreate(
            ['company_id' => $demoCompany->id, 'name' => $data['name']],
            $data + ['status' => $data['name'] === 'Fin de semana' ? 'inactive' : 'active']
        ));

        // 12 asignaciones de turno
        foreach ($demoEmployees->take(12) as $index => $employee) {
            ShiftAssignment::firstOrCreate(
                ['employee_id' => $employee->id, 'date' => Carbon::today()],
                [
                    'company_id' => $demoCompany->id,
                    'shift_id'   => $shifts[$index % 4]->id,
                ]
            );
        }

        // 12 entradas de auditoría
        $auditLogs = [
            ['login',              'auth',          User::class,             $demoAdmin->id,           0],
            ['employee.created',   'empleados',     Employee::class,         $demoEmployees[19]->id,   3],
            ['employee.updated',   'empleados',     Employee::class,         $demoEmployees[5]->id,    5],
            ['vacation.approved',  'vacaciones',    VacationRequest::class,  $demoEmployees[7]->id,    6],
            ['vacation.rejected',  'vacaciones',    VacationRequest::class,  $demoEmployees[13]->id,   8],
            ['permission.approved','permisos',      PermissionRequest::class,$demoEmployees[10]->id,   9],
            ['sick_leave.created', 'incapacidades', SickLeave::class,        $demoEmployees[18]->id,   2],
            ['document.uploaded',  'documentos',    EmployeeDocument::class, $demoEmployees[2]->id,    4],
            ['attendance.exported','reportes',      Attendance::class,       null,                      1],
            ['role.updated',       'roles',         Role::class,             3,                        12],
            ['user.created',       'usuarios',      User::class,             $demoAdmin->id,           15],
            ['settings.updated',   'configuracion', Company::class,          $demoCompany->id,         20],
        ];

        foreach ($auditLogs as [$action, $module, $entity, $entityId, $daysAgo]) {
            $log = AuditLog::firstOrCreate(
                ['company_id' => $demoCompany->id, 'action' => $action, 'entity' => $entity, 'entity_id' => $entityId],
                [
                    'user_id'    => $demoAdmin->id,
                    'module'     => $module,
                    'ip_address' => '190.85.'.rand(1, 254).'.'.rand(1, 254),
                    'new_values' => ['message' => 'Accion registrada por el sistema de demostracion'],
                ],
            );

            if ($log->wasRecentlyCreated) {
                $at = Carbon::now()->subDays($daysAgo)->subHours(rand(0, 8));
                $log->forceFill(['created_at' => $at, 'updated_at' => $at])->saveQuietly();
            }
        }

        // ── 4-9. Verticales comerciales ───────────────────────────────────────
        $this->call(AccountingSeeder::class,  false, ['forCompanyId' => $demoCompany->id, 'forAdminId' => $demoAdmin->id]);
        $this->call(CrmSeeder::class,         false, ['forCompanyId' => $demoCompany->id, 'forAdminId' => $demoAdmin->id]);
        $this->call(InventorySeeder::class,   false, ['forCompanyId' => $demoCompany->id, 'forAdminId' => $demoAdmin->id]);
        $this->call(PurchasesSeeder::class,   false, ['forCompanyId' => $demoCompany->id, 'forAdminId' => $demoAdmin->id]);
        $this->call(SalesSeeder::class,       false, ['forCompanyId' => $demoCompany->id, 'forAdminId' => $demoAdmin->id]);
        $this->call(FinanceSeeder::class,     false, ['forCompanyId' => $demoCompany->id, 'forAdminId' => $demoAdmin->id]);
        $this->call(RecruitmentSeeder::class, false, ['forCompanyId' => $demoCompany->id, 'forAdminId' => $demoAdmin->id]);

        // ── 10. Credenciales en consola (nunca en logs ni API) ────────────────
        if ($this->command) {
            $this->command->warn('⚠  CREDENCIALES DEMO — protege este output si usas CI/CD con logs públicos');
            $this->command->info('');
            $this->command->info('=== EMPRESA DEMO LISTA ===');
            $this->command->info('URL: http://localhost:3000');
            $this->command->info('');
            $this->command->info('Admin:      admin.demo@empresademo.co      / '.$password);
            $this->command->info('RRHH:       rrhh.demo@empresademo.co       / '.$password);
            $this->command->info('Supervisor: supervisor.demo@empresademo.co / '.$password);
            $this->command->info('Empleado:   empleado.demo@empresademo.co   / '.$password);
            $this->command->info('');
            $this->command->warn('⚠  Fin de credenciales demo');
        }
    }
}
