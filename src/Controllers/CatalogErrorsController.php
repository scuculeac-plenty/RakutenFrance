<?php

namespace RakutenFrance\Controllers;

use Plenty\Plugin\Controller;
use Plenty\Plugin\Http\Request;
use Plenty\Plugin\Http\Response;
use Plenty\Plugin\Log\Loggable;
use RakutenFrance\Repositories\CatalogErrorsRepository;

class CatalogErrorsController extends Controller
{
    use Loggable;

    /** @var CatalogErrorsRepository */
    private $catalogErrorsRepository;

    public function __construct(
        CatalogErrorsRepository $catalogErrorsRepository
    ) {
        $this->catalogErrorsRepository = $catalogErrorsRepository;
    }

    /**
     * Index of UI errors
     *
     * @param Request  $request
     * @param Response $response
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function index(Request $request, Response $response)
    {
        $params = $request->all();
        if (!empty($params['variationId'])) {
            $errors = $this->catalogErrorsRepository->findByVariationId((int)$params['variationId']);
            $params = $this->resetPage($errors, $params);
            return $response->json($this->paginatedResult($params, $errors));
        }
        if (!empty($params['format'])) {
            $errors = $this->catalogErrorsRepository->getByFormat($params['format']);
            $params = $this->resetPage($errors, $params);
            return $response->json($this->paginatedResult($params, $errors));
        }

        $errors = $this->catalogErrorsRepository->get();
        return $response->json($this->paginatedResult($params, $errors));
    }

    private function resetPage($errors, $params)
    {
        if (count($errors) <= $params['itemsPerPage']) {
            $params['page'] = 1;
        }

        return $params;
    }

    /**
     * Return paginated array from results
     *
     * @param array $params
     * @param array $data
     *
     * @return array
     */
    private function paginatedResult(array $params, array $data): array
    {
        $totalsCount = count($data);
        $lastPageNumber = ceil($totalsCount / $params['itemsPerPage']);

        $pageOffset = $params['itemsPerPage'] * ($params['page'] - 1);
        $pageLimit = $params['itemsPerPage'] * $params['page'];

        $firstOnPage = $pageOffset + 1;
        $lastOnPage = $pageLimit;
        $entries = [];
        for ($i = $pageOffset; $i < $pageLimit; $i++) {
            if (empty($data[$i])) {
                break;
            }
            $entries[] = $data[$i];
            $lastOnPage = $i + 1; // $i counts from 0;
        }

        return [
            'page' => $params['page'],
            'totalsCount' => $totalsCount,
            'isLastPage' => $params['page'] == $lastPageNumber,
            'lastPageNumber' => $lastPageNumber,
            'firstOnPage' => $firstOnPage,
            'lastOnPage' => $lastOnPage,
            'itemsPerPage' => $params['itemsPerPage'],
            'entries' => $entries
        ];
    }
}
