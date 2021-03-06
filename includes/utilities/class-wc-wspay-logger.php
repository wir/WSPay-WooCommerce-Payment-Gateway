<?php

if ( !defined( "ABSPATH" ) ) {
  exit;
}


if ( !class_exists( "WC_WSPay_Logger" ) ) {
  class WC_WSPay_Logger {

    /**
     * Whether or not logging is enabled.
     * @var boolean
     */
    public $is_log_enabled = false;

    /**
     * List of valid logger levels, from most to least urgent.
     * @var array
     */
    public $log_levels = [
      "emergency", "alert", "critical", "error", "warning", "notice", "info", "debug"
    ];

    /**
     * Is mailer system for certain errors enabled. Mailer system should send
     * a mail notice do defined address if there was a message logged with
     * defined min level.
     * @var boolean
     */
    private $is_mailer_enabled = false;

    /**
     * E-mail address for receving errors.
     * @var string
     */
    private $mailer_address = null;

    /**
     * Minimum log level for triggering mailer.
     * @var string
     */
    private $mailer_min_log_level = "error";

    /**
     * Minimum log level index for triggering mailer. Should be a position of
     * $mailer_min_log_level in $log_levels array.
     * @var string
     */
    private $mailer_min_log_level_index = 3;

    /**
     * Init logger.
     * @param boolean $is_log_enabled: defaults to false
     */
    public function __construct( $is_log_enabled = false ) {
      $this->is_log_enabled = $is_log_enabled;
    }

    /**
     * Enable mailer system and return true if successful.
     * @param string  $mailer_address
     * @param string $mailer_min_log_level: defaults to "error"
     * @return boolean
     */
    public function enable_mailer( $email, $min_log_level = "error" ) {
      $this->is_mailer_enabled = false;
      if ( filter_var( $email, FILTER_VALIDATE_EMAIL ) ) {
        $this->mailer_address = $email;
      } else {
        return false;
      }

      $log_level_index = array_search( $min_log_level, $this->log_levels, true );
      if ( $log_level_index ) {
        $this->mailer_min_log_level = $min_log_level;
        $this->mailer_min_log_level_index = $log_level_index;
      } else {
        return false;
      }
      $this->is_mailer_enabled = true;
      return true;
    }

    public function disable_mailer() {
      $this->is_mailer_enabled = false;
    }

    /**
     * Logs given message for given level and return true if successful, false
     * otherwise.
     * @param string $message
     * @param string $level: check $log_levels for valid level values.
     * @return boolean
     */
    public function log( $message, $level = "info" ) {
      if ( $this->is_log_enabled ) {
        if ( empty( $this->logger ) ) {
          if ( function_exists( "wc_get_logger" ) ) {
            $this->logger = wc_get_logger();
          } else {
            return false;
          }
        }

        // check if provided level is valid!
        if ( !in_array( $level, $this->log_levels ) ) {
          $this->log( "Invalid log level provided: " . $level, "debug" );
          $level = "notice";
        }

        if ( $this->is_mailer_enabled ) {
          $log_level_index = array_search( $level, $this->log_levels, true );
          if ($log_level_index && $log_level_index <= $this->mailer_min_log_level_index) {
            $this->send_mail($level, $message);
          }
        }

        $this->logger->log( $level, $message, array( "source" => "wcwspay" ) );

        return true;
      }

      return false;
    }

    /**
     *
     */
    private function send_mail($level, $message) {
      if ($this->mailer_address) {
        $subject = __( "WSpay Gateway", "wcwspay" ) . " " . ucfirst($level) .
          " " . __( "notice", "wcwspay" );

        $body = ucfirst($level) . " " . __( "event was logged at", "wcwspay" ) .
          " " . date("d.m.Y. H:i:s") . ".\n";
        $body .= __( "Event message", "wcwspay" ) . ": " . $message . "\n\n\n";
        $body .= "Neuralab WooCommerce WSpay Payment Gateway";

        wp_mail($this->mailer_address, $subject, $body);
      }
    }

  }
}
