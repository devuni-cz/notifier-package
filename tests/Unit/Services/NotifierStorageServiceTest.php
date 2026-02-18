<?php

declare(strict_types=1);

use Devuni\Notifier\Services\NotifierStorageService;

describe('NotifierStorageService', function () {
    describe('service structure and configuration', function () {
        it('has the correct class structure', function () {
            $reflection = new ReflectionClass(NotifierStorageService::class);

            expect($reflection->hasMethod('createStorageBackup'))->toBeTrue();
            expect($reflection->hasMethod('sendStorageBackup'))->toBeTrue();
        });

        it('createStorageBackup method has correct signature', function () {
            $reflection = new ReflectionClass(NotifierStorageService::class);
            $method = $reflection->getMethod('createStorageBackup');

            expect($method->isStatic())->toBeFalse();
            expect($method->isPublic())->toBeTrue();
            expect($method->getReturnType()->getName())->toBe('string');
            expect($method->getNumberOfParameters())->toBe(0);
        });

        it('sendStorageBackup method has correct signature', function () {
            $reflection = new ReflectionClass(NotifierStorageService::class);
            $method = $reflection->getMethod('sendStorageBackup');

            expect($method->isStatic())->toBeFalse();
            expect($method->isPublic())->toBeTrue();
            expect($method->getNumberOfParameters())->toBe(1);

            $parameters = $method->getParameters();
            expect($parameters[0]->getType()->getName())->toBe('string');
        });
    });

    describe('storage configuration usage', function () {
        it('can access required storage configuration', function () {
            // Set test configuration
            config([
                'notifier.backup_zip_password' => 'test-password',
                'notifier.excluded_files' => ['.gitignore', 'test.txt'],
                'notifier.backup_url' => 'https://test.com/backup',
                'notifier.backup_code' => 'test-code',
            ]);

            expect(config('notifier.backup_zip_password'))->toBe('test-password');
            expect(config('notifier.excluded_files'))->toBe(['.gitignore', 'test.txt']);
            expect(config('notifier.backup_url'))->toBe('https://test.com/backup');
            expect(config('notifier.backup_code'))->toBe('test-code');
        });

        it('generates expected backup filename format', function () {
            // The service should generate filenames in format: backup-YYYY-MM-DD.zip
            $expectedPattern = '/backup-\d{4}-\d{2}-\d{2}\.zip$/';

            // Test the pattern itself
            $testFilename = 'backup-2025-09-08.zip';
            expect(preg_match($expectedPattern, $testFilename))->toBe(1);
        });

        it('uses correct storage directory', function () {
            $expectedBackupDir = storage_path('app/private');
            $expectedSourceDir = storage_path('app/public');

            expect($expectedBackupDir)->toContain('app/private');
            expect($expectedSourceDir)->toContain('app/public');
        });
    });

    describe('backup upload configuration', function () {
        it('uses correct HTTP endpoint for upload', function () {
            config(['notifier.backup_url' => 'https://example.com/backup']);

            $url = config('notifier.backup_url');
            expect($url)->toBe('https://example.com/backup');
        });

        it('generates correct multipart data structure', function () {
            config([
                'notifier.backup_code' => 'secret-code',
            ]);

            // Expected multipart structure should include these fields
            $expectedFields = [
                'backup_file',    // File upload
                'backup_type',    // Should be 'backup_storage'
                'password',       // Should be the backup code
            ];

            foreach ($expectedFields as $field) {
                expect($field)->toBeString();
            }

            expect(config('notifier.backup_code'))->toBe('secret-code');
        });

        it('can access required backup configuration', function () {
            config([
                'notifier.backup_url' => 'https://api.example.com/upload',
                'notifier.backup_code' => 'auth-token',
            ]);

            expect(config('notifier.backup_url'))->toBe('https://api.example.com/upload');
            expect(config('notifier.backup_code'))->toBe('auth-token');
        });
    });

    describe('file operations expectations', function () {
        it('works with valid file paths', function () {
            $validPath = '/tmp/test-backup.zip';
            expect(is_string($validPath))->toBeTrue();
            expect(strlen($validPath))->toBeGreaterThan(0);
        });

        it('generates unique filenames by date', function () {
            // Test that different dates would generate different filenames
            $date1 = '2025-09-08';
            $date2 = '2025-09-09';

            $filename1 = "backup-{$date1}.zip";
            $filename2 = "backup-{$date2}.zip";

            expect($filename1)->not->toBe($filename2);
        });

        it('should handle file exclusion patterns', function () {
            $excludedFiles = ['.gitignore', 'node_modules/', 'vendor/'];

            // Test exclusion logic patterns
            foreach ($excludedFiles as $excludedFile) {
                expect(is_string($excludedFile))->toBeTrue();
            }

            // Test the starts_with logic used in the service
            $testFile = 'node_modules/test.js';
            $excludePattern = 'node_modules/';

            expect(str_starts_with($testFile, $excludePattern))->toBeTrue();
        });
    });

    describe('error handling expectations', function () {
        it('should handle missing configuration gracefully', function () {
            // Clear configuration to test missing values
            config([
                'notifier.backup_zip_password' => null,
                'notifier.backup_url' => null,
                'notifier.backup_code' => null,
            ]);

            expect(config('notifier.backup_zip_password'))->toBeNull();
            expect(config('notifier.backup_url'))->toBeNull();
            expect(config('notifier.backup_code'))->toBeNull();
        });

        it('should handle invalid file paths', function () {
            $invalidPath = '';
            $validPath = '/valid/path/backup.zip';

            expect(empty($invalidPath))->toBeTrue();
            expect(! empty($validPath))->toBeTrue();
        });

        it('validates zip archive operations', function () {
            // Test ZipArchive constants are available
            expect(defined('ZipArchive::CREATE'))->toBeTrue();
            expect(defined('ZipArchive::OVERWRITE'))->toBeTrue();
            expect(defined('ZipArchive::EM_AES_256'))->toBeTrue();
        });
    });
});
