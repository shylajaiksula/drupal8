<?php

namespace Drupal\node_limit\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Database\Connection;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Url;
use Drupal\Core\Link;

/**
 * Controller routines for Browser push notification.
 */
class NodeLimitListController extends ControllerBase {

  protected $database;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
        $container->get('database')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(Connection $database) {
    $this->database = $database;
  }

  /**
   * List of all node_limit lists .
   */
  public function node_limitList() {
    // The table description.
    $header = [
      [
        'data' => $this->t('Id'),
      ],
      [
        'data' => $this->t('Title'),
      ],
      ['data' => $this->t('Type')],
      ['data' => $this->t('NLimit')],
      ['data' => $this->t('Edit')],
      ['data' => $this->t('Delete')],
    ];
    $getFields = [
      'lid',
      'type',
      'nlimit',
      'title',
    ];
    $query = $this->database->select('node_limit');
    $query->fields('node_limit', $getFields);
    // Limit the rows to 50 for each page.
    $pager = $query->extend('Drupal\Core\Database\Query\PagerSelectExtender')
        ->limit(50);
    $result = $pager->execute();

    // Populate the rows.
    $rows = [];
    foreach ($result as $row) {
      $edit_url = Url::fromRoute('entity.node_limit.edit_form', array('node_limit' => $row->lid));
      $edit_link = Link::fromTextAndUrl(t('Edit'), $edit_url);
      $edit_link = $edit_link->toRenderable();
      // If you need some attributes.
      $edit_link['#attributes'] = array('class' => array('button'));


      $delete_url = Url::fromRoute('entity.node_limit.delete_form', array('node_limit' => $row->lid));
      $delete_link = Link::fromTextAndUrl(t('Delete'), $delete_url);
      $delete_link = $delete_link->toRenderable();
      // If you need some attributes.
      $delete_link['#attributes'] = array('class' => array('button'));
      $rows[] = [
        'data' => [
          'id' => $row->lid,
          'title' => $row->title,
          'type' => $row->type,
          'nlimit' => $row->nlimit,
          render($edit_link),
          render($delete_link)
        ],
      ];
    }
    if (empty($rows)) {
      $markup = $this->t('No record found.');
    }
    else {
      $markup = $this->t('List of All Node Limit');
    }
    $build = [
      '#markup' => $markup,
    ];
    // Generate the table.
    $build['config_table'] = [
      '#theme' => 'table',
      '#header' => $header,
      '#rows' => $rows,
    ];

    $build['pager'] = [
      '#type' => 'pager',
    ];
    return $build;
  }

}
