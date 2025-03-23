<?php

namespace OleTrenner\HowMany;


class Api {
    public $days_limit = 14;

    public function __construct(
        protected MeasurementService $measurementService,
        protected Database $db,
    )
    {
    }

    public function handle()
    {
        $endpoint = $_REQUEST['endpoint'] ?? false;
        $params = json_decode(wp_unslash($_REQUEST['params'] ?? 'false'), true);
        $method = 'handle_' . $endpoint;

        try {
            if (method_exists($this, $method)) {
                $result = [
                    'status' => 'ok',
                    'result' => $this->$method($params),
                ];
            } else {
                throw new \Exception('endpoint not found');
            }
        } catch(\Exception $e) {
            http_response_code(500);
            $result = [
                'status' => 'error',
                'error' => $e->getMessage(),
            ];
        }

        header('Content-Type: application/json');
        echo json_encode($result);
        exit;
    }

    protected function handle_measurements(mixed $params): mixed
    {
        return $this->measurementService->getMeasurementDefinitions();
    }

    protected function handle_measurement(mixed $params): mixed
    {
        $key = $params['key'] ?? null;
        $resolution = isset($params['resolution']) ? Resolution::tryFrom($params['resolution']) : null;
        $interval = $params['interval'] ?? null;
        $refresh = $params['refresh'] ?? false;
        return $this->measurementService->applyMeasurement($key, $resolution, $interval, $refresh);
    }
}