<?php

namespace Drupal\exchange_rate\Controller;

use Drupal\Component\Datetime\DateTimePlus;
use Drupal\Component\Utility\Unicode;
use Drupal\Core\Controller\ControllerBase;
use Drupal\exchange_rate\ExchangeRateManager;

/**
 * Class ExchangeRateController
 *
 * @package Drupal\exchange_rate
 */
class ExchangeRateController extends ControllerBase {

  /**
   * @var \Drupal\exchange_rate\ExchangeRateManager
   */
  protected $manager;

  /**
   * ExchangeRateController constructor.
   *
   * @param \Drupal\exchange_rate\ExchangeRateManager $exchangeRateManager
   */
  public function __construct(ExchangeRateManager $exchangeRateManager) {
    $this->manager = $exchangeRateManager;
  }

  public function content() {
    // Init content variable and extract current timestamp.
    $content = [];
    $timestamp = time();

    /** @var DateTimePlus $startDate */
    $startDate = DateTimePlus::createFromTimestamp($timestamp)
      ->sub(\DateInterval::createFromDateString('10 day'));

    /** @var DateTimePlus $endDate */
    $endDate = DateTimePlus::createFromTimestamp($timestamp);

    // Setup resources types to consume.
    $resources = [
      317 => 'buying',
      318 => 'selling',
    ];
    foreach ($resources as $key => $value) {
      /** @var array $resource */
      $resource = $this->manager->getExchangeRates([
        $startDate->format('d/m/Y'),
        $endDate->format('d/m/Y'),
      ], $key);

      // Build up table type for each resource type.
      $content[$value] = [
        '#type' => 'table',
        '#attributes' => [
          'class' => [
            'exchange-rate-table',
          ],
        ],
        '#caption' => $this->t(
          Unicode::ucfirst($value) . ' rate'
        ),
        '#header' => [
          $this->t('Date'),
          $this->t('Rate'),
        ],
        '#rows' => array_map(function ($item) {
          return [
            $item['date'],
            $item['rate'],
          ];
        }, $resource),
      ];
    }

    return $content;
  }
}
