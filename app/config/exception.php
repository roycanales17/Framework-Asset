<?php 

    namespace Core;

    class AppException extends \Exception {
        
        private string $messageType;

        public function __construct( $message, $type = "error" | "warning" | "success" ) 
        {
            parent::__construct( $message );
            $this->messageType = $type;
        }

        function getType() {
            return $this->messageType;
        }
    }