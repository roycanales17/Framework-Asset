<?php

namespace Illuminate\Http;
use finfo;

trait FileValidation
{
    protected function validateFile( string $key ): bool
    {
        if ( isset( $this->request ) && $this->request[ 'FILES' ] )
            $files = $this->request[ 'FILES' ];
        else
            $files = $_FILES;

        return array_key_exists( strtoupper( $key ), $files );
    }

    protected function validateFileMimeType( string $key, string $allowedExtensions, string|bool $error = true ): bool
    {
        if ( $this->validateRequiredFile( $key ) )
        {
            $files = $this->getInput( $key );
            $fileInfo = new finfo( FILEINFO_MIME_TYPE );
            $allowedMimes = explode( ',', $allowedExtensions );

            if ( is_array( $files[ 'name' ] ) )
            {
                for ( $i = 0; $i < count( $files[ 'name' ] ); $i++ )
                {
                    $attr = $this->getAttributes( $key, $i );
                    $fileMimeType = $fileInfo->file( $attr[ 'tmp_name' ] );

                    $found = false;
                    foreach ( $allowedMimes as $mime )
                    {
                        $fileMimeType = strtolower( str_replace( 'jpg', 'jpeg', $fileMimeType ) );
                        $mime = strtolower( str_replace( 'jpg', 'jpeg', $mime ) );

                        if ( strpos( $fileMimeType, $mime ) !== false ) {
                            $found = true;
                            break;
                        }
                    }

                    if ( !$found )
                    {
                        if ( $error === true )
                            $this->setMessage( $key, $this->getMessage( $key, 'mimes' ) ?: "Invalid file ($attr[name]), the file must be a (".implode( ', ', array_map( 'strtoupper', $allowedMimes ) ).").");

                        elseif ( is_string( $error ) )
                            $this->setMessage( $key, $error );

                        return false;
                    }
                }
            }
            else
            {
                $found = false;
                $fileExtension = $fileInfo->file( $files[ 'tmp_name' ] );

                foreach ( $allowedMimes as $mime )
                {
                    $fileMimeType = strtolower( str_replace( 'jpg', 'jpeg', $fileExtension ) );
                    $mime = strtolower( str_replace( 'jpg', 'jpeg', $mime ) );

                    if ( strpos( $fileMimeType, $mime ) !== false ) {
                        $found = true;
                        break;
                    }
                }

                if ( !$found )
                {
                    if ( $error === true )
                        $this->setMessage( $key, $this->getMessage( $key, 'mimes' ) ?: "Invalid file ($files[name]), the file must be a (".implode( ', ', array_map( 'strtoupper', $allowedMimes ) ).").");

                    elseif ( is_string( $error ) )
                        $this->setMessage( $key, $error );

                    return false;
                }
            }
        }
        return true;
    }

    protected function validateMaxFile( string $key, int $megabytes ): bool
    {
        if ( $this->validateRequiredFile( $key ) )
        {
            $files = $this->getInput( $key );
            $maxSizeInBytes = $megabytes * 1024 * 1024;

            if ( is_array( $files[ 'name' ] ) )
            {
                for ( $i = 0; $i < count( $files[ 'name' ] ); $i++ )
                {
                    $attr = $this->getAttributes( $key, $i );
                    $size = $attr[ 'size' ];

                    if ( $size > $maxSizeInBytes )
                    {
                        $this->setMessage( $key, $this->getMessage( $key, 'max' ) ?: "Must not exceed the maximum ({$megabytes}MB) file size, given file ($attr[name]).");
                        return false;
                    }
                }
            }
            else
            {
                if ( $files[ 'size' ] > $maxSizeInBytes )
                {
                    $this->setMessage( $key, $this->getMessage( $key, 'max' ) ?: "Must not exceed the maximum ({$megabytes}MB) file size, given file ($files[name]).");
                    return false;
                }
            }
        }

        return true;
    }

    protected function validateRequiredFile( string $input ): bool
    {
        $input = $this->getInput( $input );
        if ( empty( $input[ 'name' ] ) )
            return false;

        else
        {
            if ( is_array( $input[ 'name' ] ) )
            {
                foreach ( $input[ 'name' ] as $files ) {
                    if ( empty( $files ) )
                        return false;
                }
            }
        }
        return true;
    }

    protected function validateImageFile( string $input, bool|string $error = false ): bool {
        return $this->validateFileMimeType( $input, implode( ',', $this->valid_images_ext ), $error );
    }

    protected function validateFileDimensions( string $key, string $dimensionRules ): bool
    {
        if ( $this->validateRequiredFile( $key ) )
        {
            $files = $this->getInput( $key );
            $dimensionRules = explode( ',', $dimensionRules );

            if ( is_array( $files[ 'name' ] ) )
            {
                if ( !$this->validateImageFile( $key, "Failed to check the dimensions of the file, only image is allowed." ) )
                    return false;

                for ( $i = 0; $i < count( $files[ 'name' ] ); $i++ )
                    $this->validateDimension( $key, $this->getAttributes( $key, $i ), $dimensionRules );
            }
            else
            {
                if ( !$this->validateImageFile( $key, "Failed to check the dimensions of the file ($files[name]), only image is allowed." ) )
                    return false;

                $this->validateDimension( $key, $files, $dimensionRules );
            }
        }

        return true;
    }

    private function validateDimension( string $key, array $file, array $rules ): bool
    {
        $dimensions = getimagesize( $file[ 'tmp_name' ] );
        $height = $dimensions[1] ?? 0;
        $width = $dimensions[0] ?? 0;

        if ( !$dimensions )
        {
            $this->setMessage( $key, "Failed to get the dimensions of the file ($file[name]).");
            return false;
        }

        foreach ( $rules as $rule )
        {
            $rule = explode( '=', $rule );
            $name = trim( $rule[0] ?? 0 );
            $value = intval( $rule[1] ?? 0 );

            if ( !$value )
            {
                $this->setMessage( $key, "Value is required from the dimensions given [$name=$value], from the input ($key)." );
                return false;
            }

            switch ( $name )
            {
                case 'width':
                    if ( $value !== $width )
                    {
                        $this->setMessage( $key, $this->getMessage( $key, 'dimensions' ) ?: "The image width must be $value pixels, given file ($file[name]).");
                        return false;
                    }
                    break;

                case 'height':
                    if ( $value !== $height )
                    {
                        $this->setMessage( $key, $this->getMessage( $key, 'dimensions' ) ?: "The image height must be $value pixels, given file ($file[name]).");
                        return false;
                    }
                    break;

                case 'min_width':
                    if ( $width < $value )
                    {
                        $this->setMessage( $key, $this->getMessage( $key, 'dimensions' ) ?: "The image width must be at least $value pixels, given file ($file[name]).");
                        return false;
                    }
                    break;

                case 'min_height':
                    if ( $height < $value )
                    {
                        $this->setMessage( $key, $this->getMessage( $key, 'dimensions' ) ?: "The image height must be at least $value pixels, given file ($file[name]).");
                        return false;
                    }
                    break;

                case 'max_width':
                    if ( $value > $width )
                    {
                        $this->setMessage( $key, $this->getMessage( $key, 'dimensions' ) ?: "The image width must not exceed $value pixels, given file ($file[name]).");
                        return false;
                    }
                    break;

                case 'max_height':
                    if ( $value > $height )
                    {
                        $this->setMessage( $key, $this->getMessage( $key, 'dimensions' ) ?: "The image height must not exceed $value pixels, given file ($file[name]).");
                        return false;
                    }
                    break;

                default:
                    $this->setMessage( $key, "Invalid dimension rule applied [$name=$value], from the input ($key).");
                    return false;
            }
        }

        return true;
    }

    private function getAttributes( string $input, int|bool $key = false ): array
    {
        $input = $this->getInput( $input );
        if ( is_int( $key ) )
        {
            return [
                'name' 		=> $input[ 'name' ][ $key ],
                'full_path' => $input[ 'full_path' ][ $key ],
                'type' 		=> $input[ 'type' ][ $key ],
                'tmp_name' 	=> $input[ 'tmp_name' ][ $key ],
                'error' 	=> $input[ 'error' ][ $key ],
                'size' 		=> $input[ 'size' ][ $key ]
            ];
        }
        else return $input;
    }
}