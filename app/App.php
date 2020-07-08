<?php

namespace App;

use App\Sqlite3;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Cmfcmf\OpenWeatherMap;
use Cmfcmf\OpenWeatherMap\Exception as OWMException;

require 'vendor/autoload.php';

class App
{

    protected $config = [];
    protected $wdata = [];
    protected $forecast = null;
    protected $result = null;
    protected $db = null;


    /**
     * Initialize $db objects and prepare weather data 
     * @param array $config
     */
    public function __construct($config)
    {
        if (!$config) {
            $this->errLoger('Config file is null!', 'error');
            exit('Config file is null!');
        }
        $this->config = $config;

        /* Initialize DB (Sqlite3)
         * https://github.com/morris/lessql/ 
         * */
        if ($this->db == null) {
            $this->db = new Sqlite3($config['db_path'], $config['log_path']);
            if (!$this->db) {
                exit('DataBase Connection Error!');
            }
        }

        // Parse Weather for 5 days
        if ($this->parseWeather()) {
            $this->convertWeatherToArray();
        }

        // Update DB Weather Data for 5 days
        $this->result = $this->updateDbWeatherData();
    }


    /**
     * Parse weather data for 5 days from https://openweathermap.org
     * @return boolean (false - error)
     */
    protected function parseWeather()
    {
        if (!$this->config['owm_akey']) {
            $this->errLoger('API-KEY OpenWeatherMap is null!', 'error');
            exit('There No API-KEY for OpenWeatherMap!');
        }
        // Create OpenWeatherMap object. 
        $owm = new OpenWeatherMap($this->config['owm_akey']);
        try {
            // Get forecast for the next 5 days.
            $this->forecast = $owm->getWeatherForecast(
                    $this->config['owm_sity'], // Moscow
                    $this->config['owm_unit'], // metric
                    $this->config['owm_lang'], // ru
                    '', // 
                    5                          // 5 days
                );
        } catch (OWMException $e) {
            $mess = 'OpenWeatherMap exception: ' . $e->getMessage() . ' (Code ' . $e->getCode() . ').';
            $this->errLoger($mess, 'error');
            return false;
        } catch (\Exception $e) {
            $mess = 'OpenWeatherMap exception: ' . $e->getMessage() . ' (Code ' . $e->getCode() . ').';
            $this->errLoger($mess, 'error');
            return false;
        }
        // echo '<pre>';
        // echo '<br> Array from Parse: <br>';
        // print_r($this->forecast);
        // echo '<pre>';
        return true;
    }


    /**
     * Convert Weather Object to Array
     * @return boolean (false - error)
     */
    protected function convertWeatherToArray()
    {
        $warr = [];
        $counter = 0;
        $temp_curr = 0;
        $wind_spid = 0;
        $humidity = 0;

        foreach ($this->forecast as $weather) {
            $counter = $counter + 1;
            $temp_curr += intval($weather->temperature->getValue());
            $humidity += intval($weather->humidity->getValue());
            $wind_spid += intval($weather->wind->speed->getValue());
            $day = $weather->time->day->format('Y-m-d');

            if ($counter == 1) {
                $wind_unit = $this->translateWindDirect($weather->wind->direction->getUnit());
                $weath_descr = $weather->weather->description;
                $weath_icon = $weather->weather->icon;
            }
            if ($counter == 4) {
                $warr[$day]['sity'] = $this->config['owm_sity'];
                $warr[$day]['date'] = $day;
                $warr[$day]['temp_curr'] = $temp_curr / 4;
                $temp_curr = 0;
                $warr[$day]['humidity'] = $humidity / 4;
                $humidity = 0;
                $warr[$day]['wind_spid'] = $wind_spid / 4;
                $wind_spid = 0;
                $warr[$day]['wind_dirn'] = $wind_unit;
                $warr[$day]['icon'] = $weath_icon;
                $warr[$day]['description'] = $weath_descr;
                $counter = 0;
            }
        }
        // echo '<pre>';
        // echo '<br> Array from Parse: <br>';
        // print_r($warr);
        // echo '<pre>';
        if ($warr) {
            $this->wdata = $warr;
            return true;
        } else {
            $this->errLoger('Weather data is null!', 'warning');
            return false;
        }
    }


    /**
     * Update weather data for 5 days in DB
     * @return boolean (false - error)
     */
    protected function updateDbWeatherData()
    {
        if (!$this->wdata) {
            return false;
        }
        return $this->db->updateDbData($this->wdata);
    }

    /**
     * Translate wind-unit descriptinon to RU
     * @param string $en_descr
     * @return boolean (false - error)
     */
    protected function translateWindDirect($en_descr)
    {
        $eng = [
            'S' => 'Южный',
            'SE' => 'Юго-восточный',
            'SW' => 'Юго-западный',
            'SSW' => 'Южный-Юго-западный',
            'SSE' => 'Южный-Юго-восточный',
            'N' => 'Северный',
            'NE' => 'Северо-Восточный',
            'NNE' => 'Север-северо-восточный',
            'NNW' => 'Север-северо-западный',
            'NW' => 'Северо-Западный',
            'E' => 'Восточный',
            'ESE' => 'Восточный-Юго-восточный',
            'ENE' => 'Восточный-Северо-восточный',
            'W' => 'Западный',
            'WNW' => 'Западный-северо-западный',
            'WSW' => 'Западный-Юго-западный',
        ];
        $en_descr = $en_descr ? $en_descr : 'Нет'; //If no data
        return array_key_exists($en_descr, $eng) ? $eng[$en_descr] : $en_descr;
    }


    /**
     * Get AVG temperature for cur day
     * @param string $date (Y-m-d)
     * @return array (temp, press, humdt, winds)
     */
    public function getAvgValuesDay($date)
    {
        return $this->db->getAvgVals($date);
    }

    /**
     * Get Weather Data Array from DB
     * @return array weather data for 5 days
     */
    public function getDbForecastData()
    {
        $today = date("Y-m-d");
        return $this->db->getForecastDataFromDb($today);
    }
    
    /**
     * Get Weather Data Array
     * @return array $this->wdata
     */
    public function getWeatherDataArray()
    {
        return $this->wdata;
    }

    /**
     * Get Forecast
     * @return Cmfcmf\ForecastWeather object
     */
    public function getForecast()
    {
        return $this->forecast;
    }

    /**
     * Get Result
     * @return $this->result
     */
    public function getResult()
    {
        return $this->result;
    }

    
    /**
     * Get the temperature sign
     * @return $this->result
     */
    public function getTempSign($temp)
    {
        $temp = (int)$temp;
	    return $temp > 0 ? '+'.$temp : $temp;
    }

    /**
     * Get the temperature sign
     * @return $this->result
     */
    public function getDayDate($date)
    {
        $date = strtotime($date);
        $months = array('','.01','.02','.03','.04','.05','.06','.07','.08','.09','.10','.11','.12');
        $days = array('ВС', 'ПН', 'ВТ', 'СР', 'ЧТ', 'ПТ', 'СБ');
        return $days[date('w', $date)] .', ' . (int)date('d',$date) . $months[date('n', $date)];
    }

    /**
     * Get Weather Data Array from DB
     * @return array weather data for 5 days
     */
    public function updatePostRow($data)
    {   
        if ($data) {
            return $this->db->updateRow($data);
        } else {
            return false;
        }
        
    }


    /**
     * Test data for errors
     * @param array $data
     * @return boolean (false - error)
     */
    protected function testData($data)
    {
        if ($data['error']) {
            $this->errLoger($data['error'], 'error');
            return false;
        }
    }

    /**
     * Error loging into file
     * @param string $mess
     * @param string $type
     * @return avoid
     */
    protected function errLoger($mess, $type = 'debug')
    {
        $log = new \Monolog\Logger('app');
        $log->pushHandler(
            new \Monolog\Handler\StreamHandler(
                $this->config['log_path'],
                \Monolog\Logger::DEBUG
            )
        );
        switch ($type) {
            case 'info':
                $log->addInfo($mess);
                break;
            case 'warning':
                $log->addWarning($mess);
                break;
            case 'error':
                $log->addError($mess);
                break;
            default:
                $log->addDebug($mess);
                break;
        }
    }
}
