<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;

describe('NotifierSendBackupController', function () {
    beforeEach(function () {
        Config::set('notifier.backup_code', 'test-backup-code');
        Config::set('notifier.backup_url', 'https://test-backup.com/upload');
        Config::set('notifier.backup_zip_password', 'test-password');
        Http::fake(['*' => Http::response('', 200)]);
    });

    describe('request validation', function () {
        it('returns 422 when type field is missing', function () {
            $this->postJson('/api/notifier/backup', [], [
                'X-Notifier-Token' => 'test-backup-code',
            ])->assertStatus(422);
        });

        it('returns 422 when type field has invalid value', function () {
            $this->postJson('/api/notifier/backup', ['type' => 'invalid'], [
                'X-Notifier-Token' => 'test-backup-code',
            ])->assertStatus(422);
        });

        it('accepts backup_database as a valid type value', function () {
            $response = $this->postJson('/api/notifier/backup', ['type' => 'backup_database'], [
                'X-Notifier-Token' => 'test-backup-code',
            ]);

            expect($response->status())->not->toBe(422);
        });

        it('accepts backup_storage as a valid type value', function () {
            $response = $this->postJson('/api/notifier/backup', ['type' => 'backup_storage'], [
                'X-Notifier-Token' => 'test-backup-code',
            ]);

            expect($response->status())->not->toBe(422);
        });
    });

    describe('authentication', function () {
        it('returns 401 when token header is missing', function () {
            $this->postJson('/api/notifier/backup', ['type' => 'database'])
                ->assertStatus(401);
        });

        it('returns 403 when token header is wrong', function () {
            $this->postJson('/api/notifier/backup', ['type' => 'database'], [
                'X-Notifier-Token' => 'wrong-token',
            ])->assertStatus(403);
        });

        it('returns 200 when token header is correct', function () {
            $response = $this->postJson('/api/notifier/backup', ['type' => 'database'], [
                'X-Notifier-Token' => 'test-backup-code',
            ]);

            expect($response->status())->not->toBe(401);
            expect($response->status())->not->toBe(403);
        });
    });

    describe('environment validation', function () {
        it('returns 500 when environment variables are missing', function () {
            Config::set('notifier.backup_code', '');
            Config::set('notifier.backup_url', '');
            Config::set('notifier.backup_zip_password', '');

            $this->postJson('/api/notifier/backup', ['type' => 'database'], [
                'X-Notifier-Token' => '',
            ])->assertStatus(500);
        });
    });

    describe('JSON response structure', function () {
        it('returns proper JSON structure for missing variables error', function () {
            Config::set('notifier.backup_code', '');
            Config::set('notifier.backup_url', '');
            Config::set('notifier.backup_zip_password', '');

            $response = $this->postJson('/api/notifier/backup', ['type' => 'database'], [
                'X-Notifier-Token' => '',
            ]);

            $response->assertStatus(500)
                ->assertJsonStructure(['message', 'missing_variables']);
        });
    });
});
