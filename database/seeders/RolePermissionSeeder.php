<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Daftar permission yang kamu punya
        $permissions = [
            // Auth
            'pdamintern.auth.login',
            'pdamintern.auth.register',
            'pdamintern.auth.validate-token',
            'pdamintern.auth.refresh-token',
            'pdamintern.auth.logout',
            'pdamintern.auth.me',
            'pdamintern.auth.current-session',
        
            // Applications
            'pdamintern.applications.view',
            'pdamintern.applications.create',
            'pdamintern.applications.show',
            'pdamintern.applications.update',
            'pdamintern.applications.delete',
            'pdamintern.applications.check-status',
            'pdamintern.application.mentor-mentee',
            'pdamintern.application.submission-receipt',
        
            // Daily Reports
            'pdamintern.daily-reports.view',
            'pdamintern.daily-reports.show',
            'pdamintern.daily-reports.create',
            'pdamintern.daily-reports.update',
            'pdamintern.daily-reports.delete',
            'pdamintern.daily-reports.view-mentor',
            'pdamintern.daily-reports.mentor-verification',
        
            // Final Reports
            'pdamintern.final-reports.view',
            'pdamintern.final-reports.show',
            'pdamintern.final-reports.create',
            'pdamintern.final-reports.update',
            'pdamintern.final-reports.delete',
            'pdamintern.final-reports.view-mentor',
            'pdamintern.final-reports.mentor-verification',
            'pdamintern.final-reports.view-hr',
            'pdamintern.final-reports.hr-verification',
        
            // Users
            'pdamintern.users.view',
            'pdamintern.users.show',
            'pdamintern.users.create',
            'pdamintern.users.update',
            'pdamintern.users.delete',
        
            // SchoolUnis
            'pdamintern.schoolunis.view',
            'pdamintern.schoolunis.show',
            'pdamintern.schoolunis.create',
            'pdamintern.schoolunis.update',
            'pdamintern.schoolunis.delete',
        
            // Documents
            'pdamintern.documents.view',
            'pdamintern.documents.show',
            'pdamintern.documents.create',
            'pdamintern.documents.update',
            'pdamintern.documents.delete',
            'pdamintern.documents.update-status-mentor',
        
            // Attendances
            'pdamintern.attendances.today',
            'pdamintern.attendances.view',
            'pdamintern.attendances.show',
            'pdamintern.attendances.create',
            'pdamintern.attendances.update',
            'pdamintern.attendances.delete',
        
            // Assessment Aspects
            'pdamintern.assessment-aspects.view',
            'pdamintern.assessment-aspects.show',
            'pdamintern.assessment-aspects.create',
            'pdamintern.assessment-aspects.update',
            'pdamintern.assessment-aspects.delete',
        
            // Certificate
            'pdamintern.certificate.view',
            'pdamintern.certificate.show',
            'pdamintern.certificate.create',
            'pdamintern.certificate.update',
            'pdamintern.certificate.delete',
        
            // Statistics
            'pdamintern.statistics.index',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Role intern
        $intern = Role::firstOrCreate(['name' => 'intern']);
        $intern->syncPermissions([ 
            'pdamintern.auth.login',
            'pdamintern.auth.register',
            'pdamintern.auth.validate-token',
            'pdamintern.auth.refresh-token',
            'pdamintern.auth.logout',
            'pdamintern.auth.me',
            'pdamintern.auth.current-session',

            'pdamintern.applications.view',
            'pdamintern.applications.create', 
            'pdamintern.applications.update', 
            'pdamintern.applications.check-status',  

            'pdamintern.daily-reports.view',
            'pdamintern.daily-reports.show',
            'pdamintern.daily-reports.create',
            'pdamintern.daily-reports.update',
            'pdamintern.daily-reports.delete',  

            'pdamintern.final-reports.view',
            'pdamintern.final-reports.show',
            'pdamintern.final-reports.create',
            'pdamintern.final-reports.update',
            'pdamintern.final-reports.delete', 

            'pdamintern.attendances.today',
            'pdamintern.attendances.view',
            'pdamintern.attendances.show',
            'pdamintern.attendances.create',
            'pdamintern.attendances.update',
            'pdamintern.attendances.delete',

            'pdamintern.statistics.index',
        ]);

        // Role researcher
        $researcher = Role::firstOrCreate(['name' => 'researcher']);
        $researcher->syncPermissions([
            'pdamintern.auth.login',
            'pdamintern.auth.register',
            'pdamintern.auth.validate-token',
            'pdamintern.auth.refresh-token',
            'pdamintern.auth.logout',
            'pdamintern.auth.me',
            'pdamintern.auth.current-session',

            'pdamintern.applications.view',
            'pdamintern.applications.create', 
            'pdamintern.applications.update', 
            'pdamintern.applications.check-status',  

            'pdamintern.daily-reports.view',
            'pdamintern.daily-reports.show',
            'pdamintern.daily-reports.create',
            'pdamintern.daily-reports.update',
            'pdamintern.daily-reports.delete',  

            'pdamintern.final-reports.view',
            'pdamintern.final-reports.show',
            'pdamintern.final-reports.create',
            'pdamintern.final-reports.update',
            'pdamintern.final-reports.delete',  

            'pdamintern.statistics.index',
        ]);

        $admin = Role::firstOrCreate(['name' => 'admin']);
        $admin->syncPermissions(Permission::all());
    }
}
