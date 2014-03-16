<?php

class CodeBuilder {
  private $mInput;
  private $mFields = array();
  private $mUnrecognizedFields = array();
  private $mParcelableTypes;
  private $mUnsupportedTypes;
  private $mClass;

  public function __construct( $input ) {
    // These are the Java types that can easily be written to Parcel
    $this->mParcelableTypes = array(
      'boolean' => new BooleanTransformer(),
      'Boolean' => new BooleanTransformerSafe(),
      'byte' => new IntegratedTransformer( 'Byte' ),
      'double' => new IntegratedTransformer( 'Double' ),
      'float' => new IntegratedTransformer( 'Float' ),
      'int' => new IntegratedTransformer( 'Int' ),
      'long' => new IntegratedTransformer( 'Long' ),
      'Byte' => new IntegratedTransformerSafe( 'Byte' ),
      'Double' => new IntegratedTransformerSafe( 'Double' ),
      'Float' => new IntegratedTransformerSafe( 'Float' ),
      'Integer' => new IntegratedTransformerSafe( 'Int' ),
      'Long' => new IntegratedTransformerSafe( 'Long' ),
      'String' => new IntegratedTransformer( 'String' ),
      'Bundle' => new IntegratedTransformer( 'Bundle' ),
      'Date' => new DateTransformer(),
      'List' => new ListTransformer( 'ArrayList' ),
      'AbstractList' => new ListTransformer( 'LinkedList' ),
      'AbstractSequentialList' => new ListTransformer( 'ArrayList' ),
      'ArrayList' => new ListTransformer( 'ArrayList' ),
      'AttributeList' => new ListTransformer( 'AttributeList' ),
      'CopyOnWriteArrayList' => new ListTransformer( 'CopyOnWriteArrayList' ),
      'LinkedList' => new ListTransformer( 'LinkedList' ),
      'RoleList' => new ListTransformer( 'RoleList' ),
      'RoleUnresolvedList' => new ListTransformer( 'RoleUnresolvedList' ),
      'Stack' => new ListTransformer( 'Stack' ),
      'Vector' => new ListTransformer( 'Vector' ),
      'JSONObject' => new JSONTransformer( 'Object' ),
      'JSONArray' => new JSONTransformer( 'Array' ),
    );

    // These are some of the most widespread types that cannot be written to Parcel 
    $this->mUnsupportedTypes = array(
      'File',
      'Map',
      'Runnable',
      'Thread',
      'Handler',
      'AsyncTask' 
    );

    $this->parse( $input );
  }

  private function parse( $input ) {
    $this->mInput = $input;

    // Remove single-line comments
    $input = preg_replace( '/\/\/.*/', '', $input );
    $input = preg_replace( '/[\s]{2,}/m', ' ', $input );
    $input = preg_replace( '/\{[\s]+/',  '{', $input );
    $input = preg_replace( '/^[ ]+/m', '', $input );
    $input = str_replace( array( "\n", "\r", "\t" ), '', $input );
    $this->mClass = null;

    if ( preg_match( '/class (?<class>([a-zA-Z_$][a-zA-Z\d_$]*\.)*[a-zA-Z_$][a-zA-Z\d_$]*)/', $input, $matches ) )
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
        if (preg_match('/([\w\d_.]+)(<([^>]+)>)?\s+([\w\d_]+)\s*$/i', $line, $output_array)) {
          $field = new CodeField( $output_array[4], $output_array[1], $output_array[3] );
          $this->mFields[$field->getName()] = $field;
        } else {
          array_push($this->mUnrecognizedFields, $line);
        }
      }
    }
  }

  public function getClass() {
    return $this->mClass;
  }

  public function getFields() {
    return array_values( $this->mFields );
  }

  public function getUnrecognizedFields() {
    return array_values( $this->mUnrecognizedFields );
  }

  public function getSupportLevel( CodeField $field ) {
    if (isset( $this->mParcelableTypes[ $field->getType() ] )) {
      return SupportLevel::SpecificTransformer;
    } else if (in_array( $field->getType(), $this->mUnsupportedTypes )) {
      return SupportLevel::Unsupported;
    } else {
      return SupportLevel::GeneralTransformer;
    }
  }

  public function isTypeUnconditionallyParcelable( $type ) {
    if (isset( $this->mParcelableTypes[ $type ] )) {
      return true;
    } else {
      return false;
    }
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
    // Split the original code on the opening curly-brace for the class
    $splitCode = explode( '{', $this->mInput, 2 );

    // Add Parcelable interface to class declaration
    if ( strpos( $splitCode[0], 'implements' ) == false ) {
      $code = trim( $splitCode[0] ) . " implements Parcelable {" . $splitCode[1];
    } else {
      $code = trim( $splitCode[0] ) . ", Parcelable {" . $splitCode[1];
    }

    // Remove the last curly-brace to allow for the new code
    $code = rtrim( $code, '}' );

    $code .= "\n    protected " . $this->mClass . "(Parcel in) {\n";

    $reads = array();
    $writes = array();

    foreach ( $this->getFields() as $field ) {
      if ( in_array( $field->getName(), $selectedFields )) {
        $level = $this->getSupportLevel($field);
        if ($level == SupportLevel::SpecificTransformer) {
          $transformer = $this->mParcelableTypes[$field->getType()];
          $reads[] = $this->padCodeLeft( $transformer->getReadCode( $field ) );
          $writes[] = $this->padCodeLeft( $transformer->getWriteCode( $field ) );
        } else if ($level == SupportLevel::GeneralTransformer) {
          $transformer = new ValueTransformer();
          $reads[] = $this->padCodeLeft( $transformer->getReadCode( $field ) );
          $writes[] = $this->padCodeLeft( $transformer->getWriteCode( $field ) );
        }
      }
    }

    if ( count( $reads ) > 0 ) {
      $code .= implode( "\n", $reads );
    }

    $code .= "
    }

    @Override
    public int describeContents() {
        return 0;
    }

    @Override
    public void writeToParcel(Parcel dest, int flags) {
";

    if ( count( $writes ) > 0 ) {
      $code .= implode( "\n", $writes );
    }

    $code .= "
    }

    @SuppressWarnings(\"unused\")
    public static final Parcelable.Creator<" . $this->mClass . "> CREATOR = new Parcelable.Creator<" . $this->mClass . ">() {
        @Override
        public " . $this->mClass . " createFromParcel(Parcel in) {
            return new " . $this->mClass . "(in);
        }

        @Override
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
  private $mTypeParam;

  public function __construct( $fieldName, $type, $typeParam ) {
    $this->mFieldName = $fieldName;
    $this->mType = $type;
    $this->mTypeParam = $typeParam;
  }

  public function getName() {
    return $this->mFieldName;
  }

  public function getType() {
    return $this->mType;
  }

  public function getTypeParam() {
    return $this->mTypeParam;
  }
}

class SupportLevel
{
  const SpecificTransformer = 0;
  const GeneralTransformer = 1;
  const Unsupported = 2;
}
