<?php

namespace PolyPlugins\Speedy_Search;

use TeamTNT\TNTSearch\TNTSearch as TNTSearchTNTSearch;

class TNTSearch {
  
  private $index_path;
  private $tnt;
  private static $instance = null;
  
  /**
   * __construct
   *
   * @return void
   */
  public function __construct(){
    $this->index_path = WP_CONTENT_DIR . '/indexes/';

    $this->init();
  }
    
  /**
   * Init
   *
   * @return void
   */
  public function init() {
    $is_missing_extensions = Utils::is_missing_extensions();

    // Don't continue if missing extensions
    if ($is_missing_extensions) {
      return;
    }

    if (!$this->index_path_exists()) {
      $this->create_index_path();
      $this->secure_index_path();
    }

    $this->init_tnt();
  }
  
  /**
   * Init TNT
   *
   * @return void
   */
  public function init_tnt() {
    $this->tnt = new TNTSearchTNTSearch;
    
    $this->tnt->loadConfig(array(
      'driver'    => 'filesystem',
      'storage'   => $this->index_path,
      'stemmer'   => \TeamTNT\TNTSearch\Stemmer\PorterStemmer::class
    ));
  }
  
  /**
   * Get TNTSearch
   *
   * @return object
   */
  public function tnt() {
    return $this->tnt;
  }
  
  /**
   * Get instance
   *
   * @return object
   */
  public static function get_instance() {
    if (self::$instance === null) {
      self::$instance = new self();
    }

    return self::$instance;
  }
  
  /**
   * Check if the index path exists
   *
   * @return void
   */
  private function index_path_exists() {
    $index_path_exists = is_dir($this->index_path) ? true : false;
    
    return $index_path_exists;
  }
  
  /**
   * Create index path
   *
   * @return void
   */
  private function create_index_path() {
    mkdir($this->index_path, 0755, true);
  }
  
  /**
   * Secure the index path
   *
   * @return void
   */
  private function secure_index_path() {
    file_put_contents($this->index_path . '.htaccess', "Deny from all\n");
  }

}
