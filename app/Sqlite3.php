<?php

namespace App;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;

require 'vendor/autoload.php';

class Sqlite3
{

    private $pdo;
    private $log_path;


    /**
     * Initialize PDO object
     * @param string $db_path
     */
    public function __construct($db_path, $log_path)
    {
        $this->log_path = $log_path;
        if ($this->pdo == null) {
            try {
                $this->pdo = new \PDO('sqlite:' . $db_path);
            } catch (\PDOException $e) {
                $err_text = 'DataBase Connection Error!' . $e->getMessage();
                $this->errLoger($err_text, 'error');
                return null;
            }
        }
        return $this->pdo;
    }


    /**
     * Update Db Data
     * @param array $parse_arr
     * @return int - count added rows
     */
    public function updateDbData($parse_arr)
    {
        // Get today id
        $today = date("Y-m-d");

        // Compare array $parse_arr and delete coincidences from DB
        $forecast_db = $this->getForecastDaysFromDb($today);
        foreach ($parse_arr as $k => $arr) {
            if (in_array($k, $forecast_db)) {
                unset($parse_arr[$k]);
            }
        }
        
        // Insert new data in DB
        $last_id = 0;
        if (count($parse_arr) >= 1) {
            foreach ($parse_arr as $row) {
                $last_id = $this->insertRow($row);
            }
        }
        return $last_id;
    }


    /**
     * Get array of days forecast from today ID
     * @param in $today_id
     * @return array string dates
     */
    public function getForecastDaysFromDb($today)
    {
        $stmt = $this->pdo->query('SELECT date FROM data WHERE date >= :today;');
        $stmt->execute([':today' => $today]);
        $result = [];
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $result[] = $row['date'];
        }
        return $result;
    }
    
    /**
     * Get array forecast data from today ID
     * @param in $today_id
     * @return array of arrays
     */
    public function getForecastDataFromDb($today)
    {
        $stmt = $this->pdo->query('SELECT * FROM data WHERE date >= :today;');
        $stmt->execute([':today' => $today]);
        $result = [];
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $result[] = $row;
        }
        return $result;
    }


    /**
     * Insert a new project into the projects table
     * @param array $row
     * @return the id of the new data
     */
    public function insertRow($row)
    {
        $sql = 'INSERT INTO data (sity, date, temp_curr, humidity, wind_spid, wind_dirn, icon, description) '
            . 'VALUES(:sity, :date, :temp_curr, :humidity, :wind_spid, :wind_dirn, :icon, :description)';

        $stmt = $this->pdo->prepare($sql);
        try {
            $stmt->execute([
                ':sity' => $row['sity'],
                ':date' => $row['date'],
                ':temp_curr' => $row['temp_curr'],
                ':humidity' => $row['humidity'],
                ':wind_spid' => $row['wind_spid'],
                ':wind_dirn' => $row['wind_dirn'],
                ':icon' => $row['icon'],
                ':description' => $row['description'],
            ]);
        } catch (\PDOException $e) {
            $err_text = 'Error Insert Data!' . $e->getMessage();
            $this->errLoger($err_text, 'error');
            return null;
        }
        return $this->pdo->lastInsertId();
    }

    /**
     * Update row specified by the date col
     * @param array $data
     * @return bool true if success and falase on failure
     */
    public function updateRow($data)
    {
        // SQL statement to update status of a task to completed
        $sql = "UPDATE data "
            . "SET temp_curr = :temp_curr, "
            . "humidity = :humidity, "
            . "wind_spid = :wind_spid, "
            . "wind_dirn = :wind_dirn, "
            . "description = :description "
            . "WHERE date = :date";

        $stmt = $this->pdo->prepare($sql);

        // passing values to the parameters
        $stmt->bindValue(':date', $data['date']);
        $stmt->bindValue(':temp_curr', $data['temp_curr']);
        $stmt->bindValue(':humidity', $data['humidity']);
        $stmt->bindValue(':wind_spid', $data['wind_spid']);
        $stmt->bindValue(':wind_dirn', $data['wind_dirn']);
        $stmt->bindValue(':description', $data['description']);

        // execute the update statement
        return $stmt->execute();
    }


    /**
     * Get AVG values from database
     * @param string $curr_date (Y-m-d)
     * @return array (temp, press, humdt, winds)
     */
    public function getAvgVals($curr_date)
    {
        $date = new \DateTime($curr_date);
        $date->sub(new \DateInterval('P14D'));
        $last_14_days = $date->format('Y-m-d');
        // echo '<br> Curr date: '. $curr_date . ', 14 days later: ' . $last_14_days;
    
        $sql = 'SELECT AVG(temp_curr) AS temp, '
            . 'AVG(humidity) AS humdt, '
            . 'AVG(wind_spid) AS winds '
            . 'FROM data '
            . 'WHERE date >= :start AND date <= :end;';
        $stmt = $this->pdo->prepare($sql);
        try {
            $stmt->execute([
                ':start' => $last_14_days,
                ':end' => $curr_date,
            ]);
        } catch (\PDOException $e) {
            $err_text = 'Error Select AVG Data!' . $e->getMessage();
            $this->errLoger($err_text, 'error');
            return null;
        }
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }



    /**
     * Error loging into file
     * @param string $mess
     * @param string $type
     * @return avoid
     */
    protected function errLoger($mess, $type = 'debug')
    {
        $log = new \Monolog\Logger('dbase');
        $log->pushHandler(
            new \Monolog\Handler\StreamHandler(
                $this->log_path,
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
