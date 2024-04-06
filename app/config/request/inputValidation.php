<?php

namespace Illuminate\Http;

trait InputValidation
{
    private function validateMin( string $value, int $min, string|int|null &$key = null ): bool
    {
        if ( is_array( $value ) ) {
            foreach ( $value as $input_key => $key_value ) {
                if ( !( strlen( $key_value ) > $min ) ) {
                    $key = $input_key;
                    return false;
                }
            }
        }
        else {
            if ( !( strlen( $value ) > $min ) )
                return false;
        }

        return true;
    }

    private function validateMax( string $value, int $max, string|int|null &$key = null ): bool
    {
        if ( is_array( $value ) ) {
            foreach ( $value as $input_key => $key_value ) {
                if ( !( strlen( $key_value ) < $max ) ) {
                    $key = $input_key;
                    return false;
                }
            }
        }
        else {
            if ( !( strlen( $value ) < $max ) )
                return false;
        }

        return true;
    }

    private function validateArray( $value, string|int|null &$key = null ): bool {
        return is_array( $value );
    }

    private function validateNull( $value, string|int|null &$key = null ): bool
    {
        if ( is_array( $value ) ) {
            foreach ( $value as $input_key => $key_value ) {
                if ( !is_null( $key_value ) ) {
                    $key = $input_key;
                    return false;
                }
            }
        }
        else {
            if ( !is_null( $value ) )
                return false;
        }

        return true;
    }

    private function validateNumeric( $value, string|int|null &$key = null ): bool
    {
        if ( is_array( $value ) ) {
            foreach ( $value as $input_key => $key_value ) {
                if ( !is_numeric( $key_value ) ) {
                    $key = $input_key;
                    return false;
                }
            }
        }
        else {
            if ( !is_numeric( $value ) )
                return false;
        }

        return true;
    }

    private function validateString( $value, string|int|null &$key = null ): bool
    {
        if ( is_array( $value ) ) {
            foreach ( $value as $input_key => $key_value ) {
                if ( !is_string( $key_value ) ) {
                    $key = $input_key;
                    return false;
                }
            }
        }
        else {
            if ( !is_string( $value ) )
                return false;
        }

        return true;
    }

    private function validateInteger( $value, string|int|null &$key = null ): bool
    {
        if ( is_array( $value ) ) {
            foreach ( $value as $input_key => $key_value ) {
                if ( !is_integer( $key_value ) ) {
                    $key = $input_key;
                    return false;
                }
            }
        }
        else {
            if ( !is_integer( $value ) )
                return false;
        }

        return true;
    }

    private function validateEmail( $email, string|int|null &$key = null ): bool
    {
        if ( is_array( $email ) ) {
            foreach ( $email as $input_key => $key_value ) {
                if ( !filter_var( $key_value, FILTER_VALIDATE_EMAIL ) ) {
                    $key = $input_key;
                    return false;
                }
            }
        }
        else {
            if ( !filter_var( $email, FILTER_VALIDATE_EMAIL ) )
                return false;
        }

        return true;
    }

    private function validateRequired( $value, string|int|null &$key = null ): bool
    {
        if ( is_array( $value ) ) {
            foreach ( $value as $input_key => $key_value ) {
                if ( empty( trim( $key_value ) ) ) {
                    $key = $input_key;
                    return false;
                }
            }
        }
        else {
            if ( empty( trim( $value ) ) )
                return false;
        }
        return true;
    }

    private function validatePassword( string $value, &$res = '' ): bool
    {
        if ( strlen( $value ) < 8 )
            $res = "Password must be at least 8 characters long.";

        elseif ( !preg_match('/[A-Z]/', $value ) )
            $res = "Password must contain at least one uppercase letter.";

        elseif ( !preg_match( '/[a-z]/', $value ) )
            $res = "Password must contain at least one lowercase letter.";

        elseif ( !preg_match('/[0-9]/', $value ) )
            $res = "Password must contain at least one number.";

        elseif ( !preg_match( '/[\'^£$%&*()}{@#~?><>,|=_+¬-]/', $value ) )
            $res = "Password must contain at least one special character.";

        return !empty( $res );
    }

    private function validateRules( string $key, string $rule_key, mixed $rule_val ): bool
    {
        if ( $this->validateFile( $key ) )
        {
            if ( in_array( $rule_key, $this->excluded_files_rules ) )
            {
                $this->setMessage( $key , "Invalid file." );
                return false;
            }
        }
        else
        {
            if ( in_array( $rule_key, $this->excluded_non_files_rules ) )
            {
                $this->setMessage( $key , "Invalid input field." );
                return false;
            }
        }

        if ( in_array( $rule_key, $this->required_rules_values ) && !$rule_val )
        {
            $this->setMessage( $key , "Rule `$rule_key` should have value." );
            return false;
        }
        elseif ( !in_array( $rule_key, $this->required_rules_values ) && !$this->validateFile( $key ) && $rule_val )
        {
            $this->setMessage( $key , "Invalid value." );
            return false;
        }

        return true;
    }
}