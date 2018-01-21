<?php

namespace Drupal\give\ProblemLog;

/**
 * Class ProblemLog.
 */
class ProblemLog {

  /**
   * Log a problem.
   *
   * @param string $donation_uuid
   *   The UUID of the donation presently being saved.
   *
   * @param string $type
   *   A one or two word categorization of the problem.
   *
   * @param string $detail
   *   A description of the problem with all relevant and available details.
   *
   * @return bool
   *   True for a successful insertion of the problem record.
   *
   * @throws \Exception
   *   When the database insert fails.
   *
   * @see db_insert()
   */
  public static function log(string $donation_uuid, string $type, string $detail) {
    $return_value = NULL;
    $entry = [
      'donation_uuid' => $donation_uuid,
      'type' => $type,
      'detail' => $detail,
      'timestamp' => time(),
    ];
    try {
      $return_value = db_insert('give_problem')
        ->fields($entry)
        ->execute();
    }
    catch (\Exception $e) {
      \Drupal::logger('give')->error('db_insert failed. Message = %message, query= %query', [
        '%message' => $e->getMessage(),
        '%query' => $e->query_string,
      ]);
    }
    return (bool) $return_value;
  }

  /**
   * Load problems related to a given donation by donation uuid.
   *
   * @param string $donation_uuid
   *   The UUID of the donation that is of interest.
   *
   * @return object
   *   An object containing the loaded entries if found.
   */
  public static function load(string $donation_uuid) {
    return db_query('SELECT type, detail, timestamp FROM give_problem WHERE donation_uuid = :donation_uuid', [':donation_uuid' => $donation_uuid])->fetchAll();
  }

  /**
   * Load problem log records joined with donation records.
   *
   * TODO write this function and use it to add a page to admin/reports
   * see web/core/modules/dblog/src/Controller/DbLogController.php overview()
   * @see db_select()
   * @see http://drupal.org/node/310075
   */
  public static function listAll() {
    $select = db_select('dbtng_example', 'e');
    // Join the users table, so we can get the entry creator's username.
    $select->join('users_field_data', 'u', 'e.uid = u.uid');
    // Select these specific fields for the output.
    $select->addField('e', 'pid');
    $select->addField('u', 'name', 'username');
    $select->addField('e', 'name');
    $select->addField('e', 'surname');
    $select->addField('e', 'age');
    // Filter only persons named "John".
    $select->condition('e.name', 'John');
    // Filter only persons older than 18 years.
    $select->condition('e.age', 18, '>');
    // Make sure we only get items 0-49, for scalability reasons.
    $select->range(0, 50);

    $entries = $select->execute()->fetchAll(\PDO::FETCH_ASSOC);

    return $entries;
  }

}
