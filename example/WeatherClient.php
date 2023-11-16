<?php

namespace RestClient\Example;

use \DateTime;
use \DateTimeImmutable;
use \RestClient;
use \RestClient\Params;
use \RestClient\Attributes\{ Param, Header};

const VALID_WFOS = ['AKQ', 'ALY', 'BGM', 'BOX', 'BTV', 'BUF', 'CAE', 'CAR', 'CHS', 'CLE', 'CTP', 'GSP', 'GYX', 'ILM', 'ILN', 'LWX', 'MHX', 'OKX', 'PBZ', 'PHI', 'RAH', 'RLX', 'RNK', 'ABQ', 'AMA', 'BMX', 'BRO', 'CRP', 'EPZ', 'EWX', 'FFC', 'FWD', 'HGX', 'HUN', 'JAN', 'JAX', 'KEY', 'LCH', 'LIX', 'LUB', 'LZK', 'MAF', 'MEG', 'MFL', 'MLB', 'MOB', 'MRX', 'OHX', 'OUN', 'SHV', 'SJT', 'SJU', 'TAE', 'TBW', 'TSA', 'ABR', 'APX', 'ARX', 'BIS', 'BOU', 'CYS', 'DDC', 'DLH', 'DMX', 'DTX', 'DVN', 'EAX', 'FGF', 'FSD', 'GID', 'GJT', 'GLD', 'GRB', 'GRR', 'ICT', 'ILX', 'IND', 'IWX', 'JKL', 'LBF', 'LMK', 'LOT', 'LSX', 'MKX', 'MPX', 'MQT', 'OAX', 'PAH', 'PUB', 'RIW', 'SGF', 'TOP', 'UNR', 'BOI', 'BYZ', 'EKA', 'FGZ', 'GGW', 'HNX', 'LKN', 'LOX', 'MFR', 'MSO', 'MTR', 'OTX', 'PDT', 'PIH', 'PQR', 'PSR', 'REV', 'SEW', 'SGX', 'SLC', 'STO', 'TFX', 'TWC', 'VEF', 'AER', 'AFC', 'AFG', 'AJK', 'ALU', 'GUM', 'HPA', 'HFO', 'PPG', 'STU', 'NH1', 'NH2', 'ONA', 'ONP'];

enum Unit: string {
    case US = 'us';
    case SI = 'si';
}

class Location {
    public function __construct(
        public string $wfo, 
        public int $x, 
        public int $y
    ) {
        if(!in_array($wfo, VALID_WFOS))
            throw new \Exception("Invalid WFO: $wfo");
    }
}

class ForecastPeriod {
    public string $name;
    public string $short_forecast;
    public string $detailed_forecast;
    public DateTime $start;
    public DateTime $end;
    public float $probability_of_precipitation;
    public float $dewpoint;
    public float $relative_humidity;
    public string $wind_direction;
    
    public function __construct(object $data) {
        $this->name = $data->name;
        $this->short_forecast = $data->shortForecast;
        $this->detailed_forecast = $data->detailedForecast;
        $this->start = new DateTimeImmutable($data->startTime);
        $this->end = new DateTimeImmutable($data->endTime);
        $this->probability_of_precipitation = $data->probabilityOfPrecipitation->value;
        $this->dewpoint = $data->dewpoint->value;
        $this->relative_humidity = $data->relativeHumidity->value;
        $this->wind_direction = $data->windDirection;
    }

}

class Forecast {
    public DateTime $created;
    public DateTime $updated;
    public int $elevation;
    public array $periods;

    public function __construct(object $data) {
        $data = $data->properties;
        $this->periods = array_map(fn($_) => new ForecastPeriod($_), $data->periods);
        $this->created = new DateTimeImmutable($data->generatedAt);
        $this->updated = new DateTimeImmutable($data->updateTime);
        $this->elevation = $data->elevation->value;
    }
}

class WeatherClient extends RestClient {
    use \RestClient\Attributes;
    /** 
     * Defines a RESTful resource.
     * 
     * Specifies URL parts, query parameters, headers, etc.
     * Generates documentation.
     */
    public $allowed_verbs = ['GET'];
    public $base_url = 'https://api.example.com';
    public $user_agent = 'PHP RestClient example script (github.com/tcdent/php-restclient/example/WeatherClient.php)';
    public $headers = [
        'Accept' => 'application/ld+json', 
    ];
    public $decoders = [
        'ld+json' => 'json_decode'
    ];

    // public function __construct(...$args) {
    //     parent::__construct(...$args);
    //     $this->headers['Authorization'] = "Bearer: ".getenv('API_TOKEN');
    // }

    // public function post_json(string $url, JsonSerializable $data) : Response {
    //     return $this->post($url, json_encode($data), [
    //         'Content-Type' => 'application/json'
    //     ]);
    // }

    #[Param(string: 'units', allowed: [Unit::US, Unit::SI])]
    public function get_forecast(Location $location, array|Params $params=[]) : Forecast {
        /**
         * Get a forecast.
         * https://www.weather.gov/documentation/services-web-api#/default/gridpoint_forecast
         * @param Location $location: Location to get forecast for
         * @param Params $params: Additional query parameters
         * @return Forecast
         */
        [$wfo, $x, $y] = [$location->wfo, $location->x, $location->y];
        $response = $this->get("/gridpoints/$wfo/$x,$y/forecast", $params);
        return new Forecast($response->data);
    }
}

$weather = new WeatherClient;
$los_angeles = new Location(wfo: 'LOX', x: 118, y: 67);
$forecasts = $weather->get_forecast($los_angeles);

foreach($forecasts as $forecast) {
    echo $forecast->name, "\n";
    foreach($forecast->periods as $period) {
        echo $period->name, "\n";
        echo $period->short_forecast, "\n";
    }
}
