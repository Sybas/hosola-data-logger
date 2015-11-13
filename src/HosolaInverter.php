<?php
namespace Inverter;

use Monolog\Logger;
use PDO;

/**
 * Class HosolaInverter
 * @package Inverter
 */
class HosolaInverter extends Inverter
  {
  public $data = [];
  private $base64_databuffer;

  /** Mapping to what fields exists in the databuffer */
  const MAPPIGNG = [
      ["field" => "header", "offset" => 0, "length" => 4, "devider" => 1],
      ["field" => "generated_id_1", "offset" => 4, "length" => 4, "devider" => 1],
      ["field" => "generated_id_2", "offset" => 8, "length" => 4, "devider" => 1],
      ["field" => "unk_1", "offset" => 12, "length" => 4, "devider" => 1],
      ["field" => "inverter_id", "offset" => 15, "length" => 16, "devider" => 1],
      ["field" => "temperature", "offset" => 31, "length" => 2, "devider" => 10],
      ["field" => "vpv1", "offset" => 33, "length" => 2, "devider" => 10],
      ["field" => "vpv2", "offset" => 35, "length" => 2, "devider" => 10],
      ["field" => "vpv3", "offset" => 37, "length" => 2, "devider" => 10],
      ["field" => "ipv1", "offset" => 39, "length" => 2, "devider" => 10],
      ["field" => "ipv2", "offset" => 41, "length" => 2, "devider" => 10],
      ["field" => "ipv3", "offset" => 43, "length" => 2, "devider" => 10],
      ["field" => "iac1", "offset" => 45, "length" => 2, "devider" => 10],
      ["field" => "iac2", "offset" => 47, "length" => 2, "devider" => 10],
      ["field" => "iac3", "offset" => 49, "length" => 2, "devider" => 10],
      ["field" => "vac1", "offset" => 51, "length" => 2, "devider" => 10],
      ["field" => "vac2", "offset" => 53, "length" => 2, "devider" => 10],
      ["field" => "vac3", "offset" => 55, "length" => 2, "devider" => 10],
      ["field" => "fac1", "offset" => 57, "length" => 2, "devider" => 100],
      ["field" => "pac1", "offset" => 59, "length" => 2, "devider" => 1],
      ["field" => "fac2", "offset" => 62, "length" => 2, "devider" => 100],
      ["field" => "pac2", "offset" => 63, "length" => 2, "devider" => 1],
      ["field" => "fac3", "offset" => 65, "length" => 2, "devider" => 100],
      ["field" => "pac3", "offset" => 67, "length" => 2, "devider" => 1],
      ["field" => "etoday", "offset" => 69, "length" => 2, "devider" => 100],
      ["field" => "etotal", "offset" => 71, "length" => 4, "devider" => 10],
      ["field" => "htotal", "offset" => 75, "length" => 4, "devider" => 1],
      ["field" => "unk_2", "offset" => 79, "length" => 20, "devider" => 1],
  ];

  /**
   * HosolaInverter constructor.
   * @param Logger $logger
   */
  public function __construct(Logger $logger)
    {
    parent::__construct($logger);
    $this->ip = $this->settings["hosola-inverter"]["ip"];
    $this->port = $this->settings["hosola-inverter"]["port"];
    $this->protocol = $this->settings["hosola-inverter"]["protocol"];
    $this->serial = $this->settings["hosola-inverter"]["serial"];
    //todo verify above info

    $this->logger->addInfo("HosolaInverter object created", ["IP" => $this->ip, "port" => $this->port, "protocol" => $this->protocol, "serial" => $this->serial]);
    }

  /**
   * Method to fetch live data from the Inverter
   */
  public function fetch()
    {
    $this->logger->addInfo("Trying to fetch data", ["ip" => $this->ip, "port" => $this->port]);

    $error_code = null;
    $error_string = null;

    if(isset($this->settings["hosola-inverter"]["simulate"]) && $this->settings["hosola-inverter"]["simulate"])
      $this->socket = true;
    else
      $this->socket = @stream_socket_client($this->protocol . "://" . $this->ip . ":" . $this->port, $error_code, $error_string, 3);

    if($this->socket === false)
      {
      $this->logger->addError("Unable to create connection to device", ["ip" => $this->ip, "port" => $this->port]);
      die();
      }
    else
      {
      // (binary) read data buffer (expected 99 bytes), do not use fgets()
      if(isset($this->settings["hosola-inverter"]["simulate"]) && $this->settings["hosola-inverter"]["simulate"])
        $databuffer = base64_decode("aHNBsBV3jCQVd4wkgQETSDcwMTVEWFhYWAAAAAAAAACxC70KgQAAAAMABAAAAAkAAAAACRQAAAAAE4oAowAAAAAAAAAAAAMAAABLAAAAFwABAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAVjEuMTBWMS4xMJE==");
      else
        $databuffer = @fread($this->socket, 128);

      if($databuffer !== false)
        {
        // Get bytes received length
        $bytesreceived = strlen($databuffer);
        // If enough data is returned
        if($bytesreceived > 90)
          {
          // We have the correct data, now put this in the correct fields
          // Store for future reference
          $this->base64_databuffer = base64_encode($databuffer);
          $this->parseData($databuffer);
          }
        else
          {
          fclose($this->socket);
          $this->logger->addError("Incorrect data length, expected 99 bytes but received", ["bytes" => $bytesreceived, "data_received" => base64_encode($databuffer)]);
          die();
          }
        }
      else
        {
        fclose($this->socket);
        $this->logger->addError("No data received from devide");
        die();
        }
      }
    }

  protected function parseData($databuffer)
    {
    $this->logger->addInfo("Starting to parse the received data");
    // The inverter ID is already in plain text
    $this->data["inverter"] = substr($databuffer, 15, 10);
    // We do not get the time from the inverter so we use our own
    $this->data["timestamp"] = time();
    $this->data["datetime"] = date('Y-m-d H:i:s', $this->data["timestamp"]);

    // Now loop throug our mapping to get the correct values
    foreach(self::MAPPIGNG as $key => $element)
      {
      $value = null;

      if($element["length"] > 2)
        {
        $value = $this->getLong($databuffer, $element["offset"], $element["devider"]);
        }
      else
        {
        $value = $this->getShort($databuffer, $element["offset"], $element["devider"]);
        }

      $this->data[$element["field"]] = $value;
      }

    $this->logger->addInfo("Data from device sucessfully parsed", [$this->data]);
    return true;
    }

  /**
   * Get the Hosola specific info and send it to PVout
   */
  public function toPVOutput()
    {
    if(!$this->settings["pvoutput"]["enabled"])
      return;

    $this->logger->addInfo("Compiling data for PVOutput");

    $data = [];
    $data["d"] = date("Ymd", $this->getElement("timestamp")); //yyyymmdd
    $data["t"] = date("H:i", $this->getElement("timestamp")); //hh:mm
    $data["v1"] = $this->getElement("etoday");
    $data["v2"] = $this->getElement("pac1");
    $data["v5"] = $this->getElement("temperature");
    $data["v6"] = $this->getElement("vpv1");

    PVOutHelper::sendToPVOutput($this->settings, $data, $this->logger);
    }


  /**
   * Function to write Hosola inverter specific data to a mysql table
   */
  public function toMySQL()
    {
    if(!$this->settings["mysql"]["enabled"])
      return;

    $this->logger->addInfo("Compiling data for MySQL");

    // Create the PDO connection
    $mysql_string = "mysql:host=" . $this->settings["mysql"]["host"] . ";port=3306;dbname=" . $this->settings["mysql"]["database"];

    try
      {
      $pdo = new \PDO($mysql_string, $this->settings["mysql"]["user"], $this->settings["mysql"]["password"]);
      $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
      }
    catch(\PDOException $e)
      {
      $this->logger->addWarning("Unable to send data to MySQL server", ["error" => $e->getMessage(), "host" => $this->settings["mysql"]["host"]]);
      }

    if(!isset($pdo))
      return;

    // Build the SQL
    $sql = "INSERT INTO " . $this->settings["mysql"]["table"];
    $sql .= " (";
    $columns = join(",", array_column(self::MAPPIGNG, "field"));
    // Values not in the mapping
    $columns .= ",inverter,timestamp,raw_data_string_base64";
    $sql .= $columns . ")";

    // Now get all the values to insert
    $values = " VALUES(";
    foreach(self::MAPPIGNG as $key => $element)
      {
      $values .= "'" . $this->data[$element["field"]] . "',";
      }
    // And now the values that have no mapping
    $values .= "'" . $this->data["inverter"] . "'," . $this->data["timestamp"] . ",'" . $this->base64_databuffer . "');";

    // Merge the stuff together
    $sql .= $values;

    try
      {
      // Execute it
      $pdo->exec($sql);
      }
    catch(\PDOException $e)
      {
      $this->logger->addWarning("Unable to send data to MySQL", ["error" => $e->getMessage()]);
      }
    }

  /**
   * Returns the value of a given element
   *
   * @param $element
   * @return mixed
   */
  public function getElement($element)
    {
    return $this->data[$element];
    }

  /**
   * @return string Returns all data as a JSON encoded string
   */
  public function getJSON()
    {
    return json_encode($this->data);
    }
  }
