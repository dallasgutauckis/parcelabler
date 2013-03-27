<?php

class CodeBuilder {
  private $mInput;
  private $mFields = array();
  private $mSupportedTypes;
  private $mClass;

  public function __construct( $input ) {
    $this->mSupportedTypes = array(
      'boolean' => new BooleanTransformer(),
      'Boolean' => new BooleanTransformer(),
      'byte' => new IntegratedTransformer( 'Byte' ),
      'double' => new IntegratedTransformer( 'Double' ),
      'float' => new IntegratedTransformer( 'Float' ),
      'int' => new IntegratedTransformer( 'Int' ),
      'long' => new IntegratedTransformer( 'Long' ),
      'Byte' => new IntegratedTransformer( 'Byte' ),
      'Double' => new IntegratedTransformer( 'Double' ),
      'Float' => new IntegratedTransformer( 'Float' ),
      'Integer' => new IntegratedTransformer( 'Int' ),
      'Long' => new IntegratedTransformer( 'Long' ),
      'String' => new IntegratedTransformer( 'String' ),
      'Bundle' => new IntegratedTransformer( 'Bundle' ),
      'Date' => new DateTransformer(),
    );

    $this->parse( $input );
  }

  private function parse( $input ) {
    $this->mInput = $input;

    // Remove single-line comments
    $input = preg_replace( '/\/\/.*/', '', $input );
    $input = str_replace( array( "\n", "\r", "\t", "  " ), '', $input );
    $this->mClass = null;

    if ( preg_match( '/class (?<class>[^ ]+)/', $input, $matches ) )
    {
      $this->mClass = $matches['class'];
    }

    $commentStart = null;
    $c = null;
    $record = true;
    $newFile = '';

    for ( $i = 0; $i < strlen( $input ); $i++ ) {
      $pc = $c;
      $c = substr( $input, $i, 1 );
      $fc = substr( $input, $i + 1, 1 );
      if ( $record === true && $c == '/' && $fc == '*' ) {
        $record = false;
      }

      if ( $record === true ) {
        $newFile .= $c;
      }

      if ( $record === false && $pc == '*' && $c == '/' ) {
        $record = true;
      }
    }

    $input = $newFile;
    $input = str_replace( '}', '};', $input );
    $input = preg_replace( '/^[^{]+{/', '', $input );
    $input = implode( ";\n", explode( ';', $input ) );

    $level = 0;
    $newFile = '';
    $newFileI = 0;
    for ( $i = 0; $i < strlen( $input ); $i++ ) {
      $c = substr( $input, $i, 1 );
      if ( $c == '{' ) {
        if ( $level === 0 ) {
          $newFile = substr( $newFile, 0, strrpos( $newFile, ";" ) );
        }
        $level += 1;
      }

      if ( $level === 0 ) {
        $newFile .= $c;
        $newFileI++;
      }

      if ( $c == '}' ) {
        $level -= 1;
      }
    }

    $input = $newFile;

    // Remove static variable references
    $input = preg_replace( '/^.* static .*$\n/m', '', $input );
    // Trim trailing slashes
    $input = preg_replace( '/}+$/m', '', $input );
    // Remove final modifier
    $input = preg_replace( '/final /m', '', $input );
    // Remove scope modifiers
    $input = preg_replace( '/^(public|protected|private) /m', '', $input );
    // Remove annotations
    $input = preg_replace( '/^@[^ ]+/', '', $input );
    // Remove assignments
    $input = preg_replace( '/=.*$/m', '', $input );
    $input = str_replace( ';', '', $input );
    $input = trim( $input );

    if ( $input && $this->mClass ) {
      $lines = explode( "\n", $input );

      foreach ( $lines as $line ) {
        list( $type, $field ) = explode( ' ', $line, 2 );
        $type = trim( $type );
        $fieldName = trim( $field );
        $field = new CodeField( $fieldName, $type );

        $this->mFields[$fieldName] = $field;
      }
    }
  }

  public function getClass() {
    return $this->mClass;
  }

  public function getFields() {
    return array_values( $this->mFields );
  }

  public function isFieldSupported( CodeField $field ) {
    return $this->isSupported( $field->getType() );
  }

  public function isSupported( $type ) {
    return isset( $this->mSupportedTypes[ $type ] );
  }

  private function padCodeLeft( $code ) {
    $lines = explode( "\n", $code );
    $return = '';
    $padding = str_pad( '', 8, ' ' );

    foreach ( $lines as $line ) {
      $return .= $padding . $line . "\n";
    }

    $return = trim( $return, "\n" );

    return $return;
  }

  /**
   * @param selectedFields array
   */
  public function getOutput( $selectedFields = array() ) {
    // Start with the original code
    $code = trim( $this->mInput );
    // Remove the last curly-brace to allow for the new code
    $code = rtrim( $this->mInput, '}' );

    $code .= "\n    protected " . $this->mClass . "(Parcel in) {\n";

    $reads = array();
    $writes = array();

    foreach ( $this->getFields() as $field ) {
      if ( in_array( $field->getName(), $selectedFields ) && $this->isFieldSupported( $field ) ) {
        $transformer = $this->mSupportedTypes[$field->getType()];
        $reads[] = $this->padCodeLeft( $transformer->getReadCode( $field ) );
        $writes[] = $this->padCodeLeft( $transformer->getWriteCode( $field ) );
      }
    }

    if ( count( $reads ) > 0 ) {
      $code .= implode( "\n", $reads );
    }

    $code .= "
    }

    public int describeContents() {
        return 0;
    }

    public void writeToParcel(Parcel dest, int flags) {
";

    if ( count( $writes ) > 0 ) {
      $code .= implode( "\n", $writes );
    }

    $code .= "
    }

    public static final Parcelable.Creator<" . $this->mClass . "> CREATOR = new Parcelable.Creator<" . $this->mClass . ">() {
        public " . $this->mClass . " createFromParcel(Parcel in) {
            return new " . $this->mClass . "(in);
        }

        public " . $this->mClass . "[] newArray(int size) {
            return new " . $this->mClass . "[size];
        }
    };";

    $code .= "\n" . '}';

    return $code;
  }
}

class CodeField {
  private $mFieldName;
  private $mType;

  public function __construct( $fieldName, $type ) {
    $this->mFieldName = $fieldName;
    $this->mType = $type;
  }

  public function getName() {
    return $this->mFieldName;
  }

  public function getType() {
    return $this->mType;
  }
}
