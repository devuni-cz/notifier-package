<?php

declare(strict_types=1);

use Devuni\Notifier\Services\NotifierConfigService;

describe('NotifierConfigService', function () {
    beforeEach(function () {
        $this->service = new NotifierConfigService();
    });

    describe('checkEnvironment', function () {
        it('returns empty array when all environment variables are set', function () {
            config([
                'notifier.backup_zip_password' => 'test-password',
                'notifier.backup_code' => 'test-code',
                'notifier.backup_url' => 'https://test.com/backup',
            ]);

            $missing = $this->service->checkEnvironment();

            expect($missing)->toBeEmpty();
        });

        it('returns missing variables when backup_zip_password is empty', function () {
            config([
                'notifier.backup_zip_password' => '',
                'notifier.backup_code' => 'test-code',
                'notifier.backup_url' => 'https://test.com/backup',
            ]);

            $missing = $this->service->checkEnvironment();

            expect($missing)->toContain('BACKUP_ZIP_PASSWORD');
        });

        it('returns missing variables when backup_code is empty', function () {
            config([
                'notifier.backup_zip_password' => 'test-password',
                'notifier.backup_code' => '',
                'notifier.backup_url' => 'https://test.com/backup',
            ]);

            $missing = $this->service->checkEnvironment();

            expect($missing)->toContain('BACKUP_CODE');
        });

        it('returns missing variables when backup_url is empty', function () {
            config([
                'notifier.backup_zip_password' => 'test-password',
                'notifier.backup_code' => 'test-code',
                'notifier.backup_url' => '',
            ]);

            $missing = $this->service->checkEnvironment();

            expect($missing)->toContain('BACKUP_URL');
        });

        it('returns all missing variables when all are empty', function () {
            config([
                'notifier.backup_zip_password' => '',
                'notifier.backup_code' => '',
                'notifier.backup_url' => '',
            ]);

            $missing = $this->service->checkEnvironment();

            expect($missing)->toHaveCount(3)
                ->and($missing)->toContain('BACKUP_ZIP_PASSWORD')
                ->and($missing)->toContain('BACKUP_CODE')
                ->and($missing)->toContain('BACKUP_URL');
        });
    });
});
