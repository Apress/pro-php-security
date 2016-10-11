<?php

// loggerClass.php must be available to this class

class transaction {
  public $id;          // record ID
  public $fromAccount; // originating account ID
  public $toAccount;   // destination account ID
  public $amount;      // amount of transaction
  public $date;        // date/time

  private $db;         // database handle
  private $log;        // logger class instance

  //...

  // update function is used to correct account numbers or amounts only
  //   all updates MUST be logged.
  public function update() {
    // load current values for transaction from db
    $original = new transaction( $this->db, $this->log );
    $original->id = $this->id;
    $original->load();

    // build and attempt UPDATE query
    $query = "UPDATE transactions
              SET from = '$this->fromAccount',
                  to = '$this->toAccount',
                  amount = '$this->amount' ";
    if ( $this->db->query( $query ) ) {
      // UPDATE successful

      // build rollback query from original values
      $rollback = "UPDATE transactions
                   SET from = '$original->fromAccount',
                       to = '$original->toAccount',
                       amount = '$original->amount' ";

      // log update and rollback query
      $this->log->log( "Transaction #$this->id updated: $query" );
      $this->log->log( "Recover original values using: $rollback" );

      return TRUE;
    }
    else {
      throw new Exception( "Unable to update transaction using $query" );
    }
  } // end of update() method

}

?>