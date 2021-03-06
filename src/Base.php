<?php
/**
 * Created by PhpStorm.
 * User: carey
 * Date: 30/08/16
 * Time: 14:56
 */

namespace CreationMedia;

use CreationMedia\Utilities\General;
use CreationMedia\Utilities\Mapper;
use CreationMedia\Utilities\Cache;

class Base {

  /**
   * @var \Base
   */
  protected $f3;

  /**
   * @var \DB\SQL
   */
  protected $db;

  public $model, $models;


  public function __construct() {
    $this->f3 = \Base::instance();
    $this->cache = Cache::instance();
    $this->cache->load(Config::get('CACHE_DSN'), Config::get('CACHE_PREFIX'));
    $this->db = $this->f3->get('DB');
  }

  /**
   * @param $table
   * @param bool $return
   *
   * @return bool|\DB\SQL\Mapper
   */
  public function initOrm($table, $return = FALSE) {
    if (!$return && property_exists($this,
        'model') && $this->model instanceof \DB\SQL\Mapper) {
      return FALSE;
    }
    if ($return) {
      return new Mapper($this->db, $table);
    }
    $this->model = new Mapper($this->db, $table);
    return $this->model;
  }


  /**
   * Find property of any item
   *
   * @param $table
   * @param $id
   * @param $property
   *
   * @return bool
   */

  protected function find($table, $id, $property) {
    if (!property_exists($this->models, $table)) {
      $this->models->$table = $this->initOrm($table, TRUE);
    }

    $this->models->$table->load(['id=?', $id]);

    if ($this->models->$table->dry() || !array_key_exists($property,
        $this->models->$table->cast())) {
      return FALSE;
    }

    return $this->models->$table->$property;
  }


  protected function dumpJsonResponse(Array $a, $statusCode = 200) {
    General::flushJsonResponse($a, $statusCode);
  }

  public function cast() {
    return get_object_vars($this);

  }

  protected function auth($key, $login_page = '/admin/login') {
    if ((bool) $this->f3->get($key)) {
      return $this;
    }
    $this->f3->reroute($login_page);
  }

  protected function apiAuth($key) {
    if ((bool) $this->f3->get($key)) {
      return TRUE;
    }
    $this->f3->error(401);
  }

  protected function needs(
    $level,
    $field = 'access_level',
    $sessionKey = FALSE
  ) {
    if ($sessionKey) {
      $session = $this->f3->get($sessionKey);
    }
    else {
      $session = $this->f3->get($this->sessionKey);
    }

    if (intval($session[$field]) >= $level) {
      return TRUE;
    }
    $this->f3->error(403);
  }


}
