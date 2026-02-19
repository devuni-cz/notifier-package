<?php

declare(strict_types=1);

use Devuni\Notifier\Controllers\NotifierController;
use Devuni\Notifier\Services\NotifierConfigService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Mockery;

describe('NotifierController', function () {
    beforeEach(function () {
        // Mock the services
        $this->mockConfigService = Mockery::mock(NotifierConfigService::class);
        $this->controller = new NotifierController;
    });

    describe('controller structure', function () {
        it('can be instantiated', function () {
            $controller = new NotifierController;
            expect($controller)->toBeInstanceOf(NotifierController::class);
        });

        it('has the correct invoke method signature', function () {
            $controller = new NotifierController;
            $reflection = new ReflectionClass($controller);
            $method = $reflection->getMethod('__invoke');

            expect($method->getParameters())->toHaveCount(2);
            expect($method->getParameters()[0]->getType()->getName())->toBe(Request::class);
            expect($method->getParameters()[1]->getType()->getName())->toBe(NotifierConfigService::class);
        });
    });

    describe('__invoke method', function () {
        describe('request validation', function () {
            it('requires param field in request', function () {
                $request = new Request;

                expect(fn () => $this->controller->__invoke($request, $this->mockConfigService))
                    ->toThrow(ValidationException::class);
            });

            it('validates param field to be one of allowed values', function () {
                $request = new Request(['param' => 'invalid_param']);

                expect(fn () => $this->controller->__invoke($request, $this->mockConfigService))
                    ->toThrow(ValidationException::class);
            });
        });

        describe('environment validation', function () {
            it('returns error when environment variables are missing', function () {
                $request = new Request(['param' => 'backup_database']);

                // Mock the config service to return missing variables
                $this->mockConfigService
                    ->shouldReceive('checkEnvironment')
                    ->once()
                    ->andReturn(['DB_HOST', 'DB_USERNAME']);

                $response = $this->controller->__invoke($request, $this->mockConfigService);
                expect($response->getStatusCode())->toBe(500);

                $content = json_decode($response->getContent(), true);
                expect($content['message'])->toContain('environment variables are missing');
            });

            it('proceeds when all environment variables are set', function () {
                $request = new Request(['param' => 'backup_database']);

                // Mock the config service to return no missing variables
                $this->mockConfigService
                    ->shouldReceive('checkEnvironment')
                    ->once()
                    ->andReturn([]);

                $response = $this->controller->__invoke($request, $this->mockConfigService);
                // Should not return the missing variables error
                expect($response->getStatusCode())->not->toBe(500);
            });
        });
    });

    describe('JSON response structure', function () {
        it('returns proper JSON structure for error responses', function () {
            $request = new Request(['param' => 'backup_database']);

            // Mock the config service to return missing variables
            $this->mockConfigService
                ->shouldReceive('checkEnvironment')
                ->once()
                ->andReturn(['DB_HOST']);

            $response = $this->controller->__invoke($request, $this->mockConfigService);
            $content = json_decode($response->getContent(), true);

            expect($content)->toHaveKeys(['message', 'variables']);
        });
    });

    describe('controller design', function () {
        it('uses method injection for NotifierConfigService', function () {
            $reflection = new ReflectionClass(NotifierController::class);
            $method = $reflection->getMethod('__invoke');
            $parameters = $method->getParameters();

            expect($parameters)->toHaveCount(2);
            expect($parameters[1]->getType()->getName())->toBe(NotifierConfigService::class);
        });

        it('implements proper method injection pattern', function () {
            $controller = new NotifierController;
            expect($controller)->toBeInstanceOf(NotifierController::class);

            // Test that the controller accepts the injected service via method
            $request = new Request(['param' => 'backup_database']);

            $this->mockConfigService
                ->shouldReceive('checkEnvironment')
                ->once()
                ->andReturn(['MISSING_VAR']);

            $response = $controller->__invoke($request, $this->mockConfigService);
            expect($response)->toBeInstanceOf('Illuminate\Http\JsonResponse');
        });
    });
});
