<?php
/*
Copyright 2012 Dallas Gutauckis 

   Licensed under the Apache License, Version 2.0 (the "License");
   you may not use this file except in compliance with the License.
   You may obtain a copy of the License at

       http://www.apache.org/licenses/LICENSE-2.0

   Unless required by applicable law or agreed to in writing, software
   distributed under the License is distributed on an "AS IS" BASIS,
   WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
   See the License for the specific language governing permissions and
   limitations under the License.
*/

/**
 * Parcelabler
 *
 * An Android parcelabler creator
 *
 * @since 2012-02-22
 * @author Dallas Gutauckis <dgutauckis@myyearbook.com>
 */

?>
<html>
<head>
  <link rel="stylesheet" href="stylesheets/bootstrap.min.css" type="text/css" charset="utf-8" />
  <script type="text/javascript" src="javascripts/jquery.js"></script>
  <script type="text/javascript" src="javascripts/jquery-ui.js"></script>
  <title>parcelabler</title>
</head>
<body style="width: 100%">
<a href="https://github.com/dallasgutauckis/parcelabler"><img style="position: absolute; top: 0; right: 0; border: 0;" src="https://s3.amazonaws.com/github/ribbons/forkme_right_orange_ff7600.png" alt="Fork me on GitHub"></a>
  <div class="container">
  <div class="content">
    <h1>parcelabler</h1>
    <em>by <a href="http://dallasgutauckis.com">Dallas Gutauckis</a></em>
    <h6>for Android Parcelable implementations</h6>
<?php

$file = '';
if ( isset( $_POST['file'] ) )
{
  $file = $_POST['file'];
}

?>

<form method="POST">
  <fieldset>
    <div class="row">
      <div class="span10">
        <h3>Code</h2>
        <textarea name="file" rows="20" class="span10"><?php echo htmlentities( $file ); ?></textarea>
        <span class="help-block">Paste your full class definition into the box above to get the Parcelable implementation and options for removing fields for parceling. Don't worry, we don't save your code. A small example is available at <a href="http://dallasgutauckis.com/2012/01/20/parcelabler-for-implementing-androids-parcelable-interface/">this blog post about parcelabler</a>.</span>
      </div>
      <div class="span6">
        <h3>Fields</h3>
        <ul class="inputs-list">

<?php

$supportedTypes = array(
  'byte' => 'Byte',
  'double' => 'Double',
  'float' => 'Float',
  'int' => 'Int',
  'long' => 'Long',
  'Byte' => 'Byte',
  'Double' => 'Double',
  'Float' => 'Float',
  'Integer' => 'Int',
  'Long' => 'Long',
  'String' => 'String',
  'Bundle' => 'Bundle',
);

$input = $file;

// Remove single-line comments
$file = preg_replace( '/\/\/.*/', '', $file );
$file = str_replace( array( "\n", "\r", "\t", "  " ), '', $file );
$class = null;
if ( preg_match( '/class (?<class>[^ ]+)/', $file, $matches ) )
{
  $class = $matches['class'];
}

$commentStart = null;
$c = null;
$record = true;
$newFile = '';

for ( $i = 0; $i < strlen( $file ); $i++ ) {
  $pc = $c;
  $c = substr( $file, $i, 1 );
  $fc = substr( $file, $i + 1, 1 );
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

$file = $newFile;
$file = str_replace( '}', '};', $file );
$file = preg_replace( '/^[^{]+{/', '', $file );
$file = implode( ";\n", explode( ';', $file ) );

$level = 0;
$newFile = '';
$newFileI = 0;
for ( $i = 0; $i < strlen( $file ); $i++ ) {
  $c = substr( $file, $i, 1 );
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

$file = $newFile;

// Remove static variable references
$file = preg_replace( '/^.* static .*$\n/m', '', $file );
// Trim trailing slashes
$file = preg_replace( '/}+$/m', '', $file );
// Remove final modifier 
$file = preg_replace( '/final /m', '', $file );
// Remove scope modifiers
$file = preg_replace( '/^(public|protected|private) /m', '', $file );
// Remove annotations
$file = preg_replace( '/^@[^ ]+/', '', $file );
// Remove assignments
$file = preg_replace( '/=.*$/m', '', $file );
$file = str_replace( ';', '', $file );
$file = trim( $file );

if ( $file && $class ) {
  $postedFields = isset( $_POST['fields'] ) ? explode( ',', $_POST['fields'] ) : array();
  $lines = explode( "\n", $file );
  $allFields = array();
  foreach ( $lines as $line ) {
    list( $type, $field ) = explode( ' ', $line, 2 );
    $type = trim( $type );
    $field = trim( $field );
    $isSupported = in_array( $type, array_keys( $supportedTypes ) ) || $type == 'boolean' || $type == 'Boolean';
    $isChecked = $isSupported && ( count( $postedFields ) == 0 || false === in_array( $field, $postedFields ) || ( isset( $_POST['field'][$field] ) && !!$_POST['field'][$field] ) ) ? 'checked="checked"' : '';
    $allFields[$field] = $type;

    echo '<li>' . ( $isSupported ? '' : '<span class="label warning">Unsupported type: ' . $type . '</span>' )
         . '<label>'
         . '<input type="checkbox" ' . ( $isChecked ? 'checked="checked"' : '' ) . 'name="field[' . $field . ']" ' . ( $isSupported ? '' : 'disabled="disabled"' ) . ' />'
         . '<span>' . $field . '</span></label></li>';

    $_POST['field'][$field] = $isChecked;
  }
}

echo '</ul>
<input type="hidden" name="fields" value="' . implode( ',', array_keys( $allFields ) ) . '" />
</div>
</div>
</fieldset>
<div class="actions">
  <input type="submit" name="submit" class="btn primary" value="Build" />
</div>
</form>';

if ( isset( $_POST['submit'] ) && ! $class ) {
  echo '<div class="alert-message info"><strong>Try again...</strong> We really do need the full class definition (including the class name) to build this. I promise, we won\'t steal your code.</div>';
} else if ( isset( $_POST['submit'] ) ) {
  $fields = array();
  foreach ( $_POST['field'] as $field => $isEnabled ) {
    if ( $isEnabled && isset( $allFields[$field] ) )
    {
      // Assign the type to the filtered list
      $fields[$field] = $allFields[$field];
    }
  }

  if ( count( $fields ) > 0 ) {
    // Start with the original code
    $code = trim( $input );
    // Remove the last curly-brace to allow for the new code
    $code = rtrim( $input, '}' );
    
    $code .= "\n    protected " . $class . "(Parcel in) {\n";

    $reads = array();
    $writes = array();
    foreach ( $fields as $field => $type ) {
      if ( 0 === strcasecmp( $type, 'boolean' ) )
      {
        $reads[] = '        ' . $field . ' = in.readByte() != 0x00;';
        $writes[] = '        dest.writeByte((byte) (' . $field . ' ? 0x01 : 0x00));';
      } else if ( isset( $supportedTypes[$type] ) ) {
        $reads[] = '        ' . $field . ' = in.read' . $supportedTypes[$type] . '();';
        $writes[] = '        dest.write' . $supportedTypes[$type] . '(' . $field . ');';
      } else {
        continue;
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
    
    $code .= htmlentities("
    }

    public static final Parcelable.Creator<" . $class . "> CREATOR = new Parcelable.Creator<" . $class . ">() {
        public " . $class . " createFromParcel(Parcel in) {
            return new " . $class . "(in);
        }

        public " . $class . "[] newArray(int size) {
            return new " . $class . "[size];
        }
    };");

    $code .= "\n" . '}';

    echo '<h3>Output</h3><div class="alert-message success"><strong>Great news!</strong> Your code was parsed, you had fields for parceling, and the implementation for Parcelable is below.</div><p>Add the <a href="http://developer.android.com/reference/android/os/Parcelable.html">Parcelable</a> class to yours and add the following methods.</p><pre>' . $code . '</pre>';
  } else {
    echo '<div class="alert-message error"><strong>Oh noes :-/</strong> It looks like you don\'t have anything for parceling. Maybe it\'s my fault -- <a href="http://github.com/dallasgutauckis">let me know</a>.</div>';
  }
}

?>
  </div>
  </div>
</body>
</html>
