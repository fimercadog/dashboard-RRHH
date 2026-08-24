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
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $company = Company::firstOrCreate([
            'name' => 'Andes People Solutions',
        ], [
            'nit' => '901.245.880-3',
            'email' => 'talento@andespeople.co',
            'phone' => '+57 601 555 0188',
            'address' => 'Calle 93 #14-20, Bogota',
            'timezone' => 'America/Bogota',
            'locale' => 'es',
        ]);

        $permissionNames = [
            'dashboard.view', 'employees.manage', 'attendance.manage', 'requests.approve',
            'documents.manage', 'reports.view', 'users.manage', 'roles.manage', 'audit.view', 'settings.manage',
        ];

        foreach ($permissionNames as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        $roles = [
            'Super Admin' => $permissionNames,
            'Administrador de empresa' => $permissionNames,
            'Recursos Humanos' => ['dashboard.view', 'employees.manage', 'attendance.manage', 'requests.approve', 'documents.manage', 'reports.view'],
            'Supervisor' => ['dashboard.view', 'attendance.manage', 'requests.approve', 'reports.view'],
            'Empleado' => ['dashboard.view'],
        ];

        foreach ($roles as $roleName => $rolePermissions) {
            Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web'])->syncPermissions($rolePermissions);
        }

        $departments = collect([
            ['name' => 'Talento Humano', 'description' => 'Gestion integral del ciclo de vida del colaborador'],
            ['name' => 'Operaciones', 'description' => 'Ejecucion y seguimiento operativo'],
            ['name' => 'Finanzas', 'description' => 'Control financiero y administrativo'],
            ['name' => 'Tecnologia', 'description' => 'Producto, datos y soporte interno'],
        ])->map(fn ($data) => Department::firstOrCreate(['company_id' => $company->id, 'name' => $data['name']], $data + ['status' => 'active']));

        $positions = collect([
            ['name' => 'Gerente de Talento', 'department_id' => $departments[0]->id],
            ['name' => 'Analista de Nomina', 'department_id' => $departments[0]->id],
            ['name' => 'Coordinador de Operaciones', 'department_id' => $departments[1]->id],
            ['name' => 'Asistente Operativo', 'department_id' => $departments[1]->id],
            ['name' => 'Controller Financiero', 'department_id' => $departments[2]->id],
            ['name' => 'Analista Contable', 'department_id' => $departments[2]->id],
            ['name' => 'Ingeniero Full Stack', 'department_id' => $departments[3]->id],
            ['name' => 'Soporte TI', 'department_id' => $departments[3]->id],
        ])->map(fn ($data) => Position::firstOrCreate(['company_id' => $company->id, 'name' => $data['name']], $data + ['description' => 'Cargo base del catalogo organizacional', 'status' => 'active']));

        $names = [
            ['Camila', 'Rojas'], ['Sebastian', 'Moreno'], ['Valentina', 'Castro'], ['Mateo', 'Herrera'],
            ['Laura', 'Medina'], ['Daniel', 'Gomez'], ['Isabella', 'Torres'], ['Nicolas', 'Vargas'],
            ['Paula', 'Jimenez'], ['Andres', 'Ramirez'], ['Manuela', 'Suarez'], ['Felipe', 'Cortes'],
            ['Natalia', 'Ortega'], ['Santiago', 'Reyes'], ['Carolina', 'Mendoza'], ['Julian', 'Pardo'],
            ['Diana', 'Navarro'], ['Tomas', 'Salazar'], ['Mariana', 'Acosta'], ['Esteban', 'Mejia'],
        ];

        $employees = collect($names)->map(function ($name, $index) use ($company, $positions) {
            $position = $positions[$index % $positions->count()];

            return Employee::firstOrCreate([
                'company_id' => $company->id,
                'employee_code' => 'EMP-'.str_pad((string) ($index + 1), 4, '0', STR_PAD_LEFT),
            ], [
                'first_name' => $name[0],
                'last_name' => $name[1],
                'identification_type' => 'CC',
                'identification_number' => '10'.str_pad((string) (8000000 + $index * 1731), 8, '0', STR_PAD_LEFT),
                'email' => strtolower($name[0].'.'.$name[1]).'@andespeople.co',
                'phone' => '+57 300 '.rand(100, 999).' '.rand(1000, 9999),
                'birth_date' => Carbon::today()->subYears(rand(24, 48))->subDays(rand(0, 340)),
                'city' => 'Bogota',
                'hire_date' => Carbon::today()->subMonths(rand(2, 60)),
                'employment_status' => $index === 18 ? 'on_leave' : 'active',
                'department_id' => $position->department_id,
                'position_id' => $position->id,
                'contract_type' => ['Indefinido', 'Fijo', 'Prestacion de servicios'][$index % 3],
                'salary' => 2400000 + ($index * 185000),
                'work_schedule' => 'Lunes a viernes 08:00-17:00',
            ]);
        });

        $departments->each(fn ($department, $index) => $department->update(['manager_id' => $employees[$index]->id]));

        $demoUsers = [
            [
                'name' => 'Sofia Mercado',
                'email' => 'superadmin@andespeople.co',
                'role' => 'Super Admin',
                'employee_index' => null,
            ],
            [
                'name' => 'Camila Rojas',
                'email' => 'admin@andespeople.co',
                'role' => 'Administrador de empresa',
                'employee_index' => 0,
            ],
            [
                'name' => 'Sebastian Moreno',
                'email' => 'rrhh@andespeople.co',
                'role' => 'Recursos Humanos',
                'employee_index' => 1,
            ],
            [
                'name' => 'Valentina Castro',
                'email' => 'supervisor@andespeople.co',
                'role' => 'Supervisor',
                'employee_index' => 2,
            ],
            [
                'name' => 'Laura Medina',
                'email' => 'empleado@andespeople.co',
                'role' => 'Empleado',
                'employee_index' => 4,
            ],
        ];

        $seededUsers = collect($demoUsers)->map(function (array $demoUser) use ($company, $employees) {
            $user = User::updateOrCreate(
                ['email' => $demoUser['email']],
                [
                    'company_id' => $company->id,
                    'employee_id' => $demoUser['employee_index'] === null ? null : $employees[$demoUser['employee_index']]->id,
                    'name' => $demoUser['name'],
                    'password' => Hash::make('password'),
                    'status' => 'active',
                ],
            );

            $user->syncRoles([$demoUser['role']]);

            return $user;
        });

        $admin = $seededUsers->firstWhere('email', 'admin@andespeople.co');

        $statuses = ['present', 'present', 'present', 'late', 'absent'];
        for ($day = 0; $day < 7; $day++) {
            foreach ($employees as $index => $employee) {
                Attendance::updateOrCreate([
                    'employee_id' => $employee->id,
                    'date' => Carbon::today()->subDays($day)->toDateString(),
                ], [
                    'company_id' => $company->id,
                    'status' => $statuses[($index + $day) % count($statuses)],
                    'check_in' => ($index + $day) % 5 === 4 ? null : sprintf('08:%02d', (($index + $day) % 4) * 8),
                    'check_out' => ($index + $day) % 5 === 4 ? null : '17:00',
                    'late_minutes' => ($index + $day) % 5 === 3 ? 18 : 0,
                    'source' => 'seed',
                ]);
            }
        }

        VacationRequest::firstOrCreate(['company_id' => $company->id, 'employee_id' => $employees[4]->id, 'start_date' => Carbon::today()->addDays(12)], [
            'end_date' => Carbon::today()->addDays(18),
            'requested_days' => 5,
            'reason' => 'Vacaciones familiares programadas',
            'status' => 'pending',
        ]);

        PermissionRequest::firstOrCreate(['company_id' => $company->id, 'employee_id' => $employees[6]->id, 'start_date' => Carbon::today()->addDays(3)], [
            'type' => 'medical',
            'end_date' => Carbon::today()->addDays(3),
            'requested_days' => 1,
            'reason' => 'Cita medica especializada',
            'status' => 'pending',
        ]);

        SickLeave::firstOrCreate(['company_id' => $company->id, 'employee_id' => $employees[18]->id], [
            'start_date' => Carbon::today()->subDays(2),
            'end_date' => Carbon::today()->addDays(3),
            'days' => 6,
            'type' => 'Enfermedad general',
            'description' => 'Incapacidad certificada por EPS',
            'status' => 'active',
        ]);

        foreach ($employees->take(8) as $index => $employee) {
            EmployeeDocument::firstOrCreate(['company_id' => $company->id, 'employee_id' => $employee->id, 'name' => 'Contrato laboral'], [
                'document_type' => 'contract',
                'file_path' => 'documents/demo/contrato-'.$employee->employee_code.'.pdf',
                'issue_date' => $employee->hire_date,
                'expiration_date' => Carbon::today()->addDays(20 + $index * 9),
                'status' => $index < 3 ? 'expiring' : 'valid',
            ]);
        }

        $shift = Shift::firstOrCreate(['company_id' => $company->id, 'name' => 'Administrativo'], [
            'start_time' => '08:00',
            'end_time' => '17:00',
            'break_minutes' => 60,
            'status' => 'active',
        ]);

        foreach ($employees->take(10) as $employee) {
            ShiftAssignment::firstOrCreate(['employee_id' => $employee->id, 'date' => Carbon::today()->toDateString()], [
                'company_id' => $company->id,
                'shift_id' => $shift->id,
            ]);
        }

        AuditLog::firstOrCreate([
            'company_id' => $company->id,
            'action' => 'seeded',
            'entity' => Company::class,
            'entity_id' => $company->id,
        ], [
            'user_id' => $admin->id,
            'module' => 'system',
            'new_values' => ['message' => 'Datos demo HRMS creados'],
        ]);
    }
}
