<?php

namespace Drupal\listpage\Service;

use Drupal\Core\Logger\LoggerChannelFactory;
use GuzzleHttp\Client;

/**
 * This class holds all the api implementations for legislation endpoint.
 */
class ApiHandlerService {
  /**
   * The endpoint of the api.
   *
   * @var string
   */
  protected $endpoint;

  /**
   * The logger service.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactory
   */
  protected $logger;

  /**
   * The construtor for the class.
   *
   * @param \Drupal\Core\Logger\LoggerChannelFactory $logger
   *   The logger service to log messages to backend.
   */
  public function __construct(LoggerChannelFactory $logger) {
    $this->logger = $logger;
    $this->endpoint = "http://www.legislation.gov.uk/primary/data.feed";
  }

  /**
   * Service method to handle fetch legislation data from api.
   */
  public function fetchLegislationData($page = "") {
    $xml_data = NULL;

    $pageQueryString = $page ? "page=$page" : "";
    $completeUrl = $this->endpoint . "?$pageQueryString";

    // Setting up the client and
    // sending request with pagination to get the data from API.
    try {
      $client = new Client();
      $response = $client->get($completeUrl)->getBody();
      $xml_data = \simplexml_load_string($response);
    }
    catch (\Exception $e) {
      $errorMessage = $e->getMessage();
      $this->logger->get("legislation_api_error")->debug($errorMessage);
    }

    return $xml_data;
  }

}
