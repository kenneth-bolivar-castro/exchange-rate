<?php

namespace Drupal\exchange_rate;

use Drupal\Component\Datetime\DateTimePlus;
use Drupal\Component\Utility\Tags;
use Drupal\Core\Cache\CacheBackendInterface;
use GuzzleHttp\Client;
use Symfony\Component\Serializer\Serializer;

/**
 * Class ExchangeRateManager
 *
 * @package Drupal\exchange_rate
 */
class ExchangeRateManager {

  /**
   * @var \GuzzleHttp\Client
   */
  protected $httpClient;

  /**
   * @var \Symfony\Component\Serializer\Serializer
   */
  protected $serializer;

  /**
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * ExchangeRateManager constructor.
   *
   * @param \GuzzleHttp\Client $http_client
   * @param \Symfony\Component\Serializer\Serializer $serializer
   * @param \Drupal\Core\Cache\CacheBackendInterface $cacheBackend
   */
  public function __construct(Client $http_client, Serializer $serializer, CacheBackendInterface $cacheBackend) {
    $this->httpClient = $http_client;
    $this->serializer = $serializer;
    $this->cache = $cacheBackend;
  }

  /**
   * Consume API service by using given arguments.
   *
   * @param array $dates
   * @param int $type
   *
   * @return null|array
   */
  protected function consume(array $dates, int $type) {
    // Extract date range.
    list($start_date, $end_date) = $dates;

    // Build query string.
    $params = [
      'tcIndicador' => $type,
      'tcFechaInicio' => $start_date,
      'tcFechaFinal' => $end_date,
      'tcNombre' => time(),
      'tnSubNiveles' => 'N',
    ];

    // Define endpoint URL.
    $query_string = http_build_query($params);
    $url = sprintf(
    //@TODO: I highly recommend to use configuration instead of URL hardcoded.
      'http://indicadoreseconomicos.bccr.fi.cr/indicadoreseconomicos/WebServices/wsIndicadoresEconomicos.asmx/ObtenerIndicadoresEconomicosXML?%s',
      $query_string
    );

    // Init resource variable.
    $resource = NULL;

    try {
      // Consume service.
      $response = $this->httpClient->get($url);

      // Verify HTTP response code.
      if ($response->getStatusCode() == 200) {
        // Retrieve XML response content.
        $xml = $response->getBody()->getContents();

        // Extract content as an array.
        $resource = $this->parseXMLContent($xml);
      }
      else {
        // Throw a runtime exception.
        throw New \RuntimeException(
          'Error while consuming API. Error code: ' . $response->getStatusCode()
        );
      }
    } catch (\RuntimeException $e) {
      // Show error message and log error message.
      watchdog_exception(__METHOD__, $e);
    }

    return $resource;
  }

  /**
   * Parse XML value from resource API.
   *
   * @param string $xml
   *
   * @return array
   */
  protected function parseXMLContent(string $xml) {
    // Decode given XML content by using serializer.
    $resource = $this->serializer->decode($xml, 'xml');

    // Then parse it again to retrieve array value to iterate.
    $resource = $this->serializer->decode($resource['0'], 'xml');

    // Parse exchange rate information.
    $resource = array_map(function ($item) {
      /** @var DateTimePlus $date */
      $date = DateTimePlus::createFromFormat(DATE_ATOM, $item['DES_FECHA']);

      return [
        'date' => $date->format('d/m/Y'),
        'rate' => '₡' . number_format($item['NUM_VALOR'], 2),
      ];
    }, $resource['INGC011_CAT_INDICADORECONOMIC']);

    // Return value as an array.
    return $resource;
  }

  /**
   * Retrieve exchange rate information from cache or by consuming the API.
   *
   * @param array $dates
   * @param int $type
   *
   * @return array|null
   */
  public function getExchangeRates(array $dates, int $type) {
    // Init resource.
    $resource = NULL;

    // Build cache ID value based on given values.
    $cid = implode('::', [Tags::implode($dates), $type]);

    // Looks up value from cache.
    if ($cache = $this->cache->get($cid)) {
      $resource = $cache->data;
    }
    else {
      // Otherwise consume API resource.
      $resource = $this->consume($dates, $type);

      // When resource was properly defined.
      if (!is_null($resource)) {

        // Then include it into cache.
        $this->cache->set($cid, $resource);
      }
    }

    // Return parsed value.
    return $resource;
  }
}
