<?php

namespace Drupal\listpage\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\listpage\Service\ApiHandlerService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * The controller for legislation response.
 *
 * The class builds the legislation list page,
 * it provides the list page controller and the structured data for datatable.
 */
class ListPageController extends ControllerBase {

  /**
   * Apihandler service to perform api actions.
   *
   * @var \Drupal\listpage\Service\ApiHandlerService
   */
  protected $apiHandlers;

  /**
   * The constructor for the class.
   *
   * @param \Drupal\listpage\Service\ApiHandlerService $apiHandlers
   *   The api handler service.
   */
  public function __construct(ApiHandlerService $apiHandlers) {
    $this->apiHandlers = $apiHandlers;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('listpage.apihandlers')
    );
  }

  /**
   * The main page to show the list of legislation data as a table.
   *
   * @return array
   *   The render array for building the page.
   */
  public function content() {
    // Building a table render array for the base datatable
    // with attributes and main library.
    $build = [
      '#type' => 'table',
      '#header' => [
        'title' => 'Title',
        'year' => 'Year',
        'number' => 'Number',
        'summary' => 'Summary',
      ],
      '#attributes' => [
        'class' => ['legislation-data-table', 'ht-50'],
      ],
      '#attached' => [
        'library' => [
          'listpage/main',
        ],
      ],
    ];

    return $build;
  }

  /**
   * The get controller for record details per page.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object containing information about the current request.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   The json response containing the details of the list of records.
   */
  public function legislationData(Request $request) {
    $filteredData = [];
    $links = [];
    // Fetching the query parameter in case of pagination.
    $pageQueryValue = $request->query->get('page');

    // Fetching the simplexmlelement which contains all the data.
    $xmlElementData = $this->apiHandlers->fetchLegislationData($pageQueryValue);

    // If the api returns no value or error occurs.
    if (!$xmlElementData) {
      return new JsonResponse([
        "data" => [],
        "links" => [],
      ]);
    }

    // Iterating through the xml element to fetch and structure the record list.
    foreach ($xmlElementData->entry as $entry) {
      $prefixedElements = $entry->children("http://www.legislation.gov.uk/namespaces/metadata");
      $jsonRepresentation = json_decode(json_encode($entry), TRUE);

      $year = (string) $prefixedElements->Year->attributes()["Value"];
      $number = (string) $prefixedElements->Number->attributes()["Value"];

      // Check if title element is multivalued.
      if (gettype($jsonRepresentation['title']) == 'array') {
        $title = implode(", ", $jsonRepresentation['title']["div"]["span"]);
      }
      else {
        $title = $jsonRepresentation['title'];
      }

      // Check if summary element is multivalued.
      if (gettype($jsonRepresentation['summary']) == 'array') {
        $summary = implode(" ", $jsonRepresentation['summary']["div"]["p"]);
      }
      else {
        $summary = $jsonRepresentation['summary'];
      }

      // Prepare the total records array.
      array_push($filteredData, [
        $title,
        $year,
        $number,
        $summary,
      ]);
    }

    // Setting up prev and next links for the response.
    foreach ($xmlElementData->link as $value) {
      $rel = (string) $value->attributes()["rel"];
      $href = (string) $value->attributes()["href"];
      if ($rel == 'next' || $rel == 'prev') {
        $links[$rel] = explode("?", $href)[1];
      }
    }

    // Structuring the data for datatable to understand.
    $responseBuild = [
      "recordsTotal" => count($filteredData),
      "recordsFiltered" => count($filteredData),
      "data" => $filteredData,
      "links" => $links,
    ];

    return new JsonResponse($responseBuild);
  }

}
