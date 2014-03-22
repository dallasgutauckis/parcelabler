<?php
/*
 * Copyright 2012 Dallas Gutauckis 
 * 
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 * 
 *     http://www.apache.org/licenses/LICENSE-2.0
 * 
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

/**
 * Parcelabler
 *
 * An Android parcelabler creator
 *
 * @since 2012-02-22
 * @author Dallas Gutauckis <dallas@gutauckis.com>
 */

// Basic autoloader
function __autoload( $class ) {
  $path = 'include/' . $class . '.php';

  if ( file_exists( $path ) ) {
    require( $path );
  }
}

spl_autoload_register('__autoload');

$file = '';
if ( isset( $_POST['file'] ) )
{
  $file = trim( $_POST['file'] );
}

$postedFields = isset( $_POST['fields'] ) ? explode( ',', $_POST['fields'] ) : array();

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
    <h5>[NEW]: Also check out <a href="http://dallasgutauckis.com/2014/02/10/a-better-parcelabler/">this convenient IntelliJ/Android Studio plugin...</a></h5>

    <form method="POST">
      <fieldset>
        <div class="row">
          <div class="span10">
            <h3>Code</h3>
            <textarea name="file" rows="20" class="span10"><?php echo htmlentities( $file ); ?></textarea>
            <span class="help-block">Paste your full class definition into the box above to get the Parcelable implementation and options for removing fields for parceling. Don't worry, we don't save your code. A small example is available at <a href="http://dallasgutauckis.com/2012/01/20/parcelabler-for-implementing-androids-parcelable-interface/">this blog post about parcelabler</a>.</span>
          </div>
          <div class="span6">
            <h3>Fields</h3>
            <ul class="inputs-list">

<?php

$codeBuilder = new CodeBuilder( $file );

$unsupportedFields = array();
if ( $file && $codeBuilder->getClass() ) {
  $fields = $codeBuilder->getFields();

  foreach ( $fields as $field ) {
    $fieldName = $field->getName();
    $isChecked = '';
    $supportLevel = $codeBuilder->getSupportLevel( $field );

    if ( $supportLevel != SupportLevel::Unsupported ) {
      $isChecked = count( $postedFields ) == 0 || false === in_array( $fieldName, $postedFields ) || ( isset( $_POST['field'][$fieldName] ) && !!$_POST['field'][$fieldName] );

      echo '<li>'
       . '<label>'
       . '<input type="checkbox" ' . ( $isChecked ? 'checked="checked"' : '' ) . 'name="field[' . $fieldName . ']" />'
       . '<span>' . $fieldName . '</span></label></li>';

      $_POST['field'][$fieldName] = $isChecked;
    } else {
      array_push( $unsupportedFields, $field );
    }
  }
}

echo '</ul>';

$unrecognizedFields = $codeBuilder->getUnrecognizedFields();
if ($file && $codeBuilder->getClass() && !empty($unrecognizedFields)) {
  echo '<br/><div class="alert-message error">The following variables were not recognized (syntax error?):
  <br/><br/><ol>';
  
  foreach ($unrecognizedFields as $variable) {
    echo '<li class="error-item">' . $variable . '</li>';
  }

  echo '</ol></div>';
}

if ($file && $codeBuilder->getClass() && !empty($unsupportedFields)) {
  echo '<br/><div class="alert-message error">The following variables cannot be written to Parcel:
  <br/><br/><ol>';
  
  foreach ($unsupportedFields as $variable) {
    echo '<li class="error-item">' . $variable->getType() . ' ' . $variable->getName() . ';</li>';
  }

  echo '</ol></div>';
}

if ($file && $codeBuilder->getClass()) {
  $suspiciousTypes = array();
  $fields = $codeBuilder->getFields();

  foreach ( $fields as $field ) {
    if ( $codeBuilder->getSupportLevel( $field ) != SupportLevel::Unsupported ){
      if ( $codeBuilder->isTypeUnconditionallyParcelable($field->getType()) === false ) {
        array_push( $suspiciousTypes, $field->getType() );
      } 
      $typeParam = $field->getTypeParam();
      if ( !empty($typeParam) && $codeBuilder->isTypeUnconditionallyParcelable($typeParam) === false ) {
        array_push( $suspiciousTypes, htmlentities($typeParam) );
      }
    }
  }

  if (!empty($suspiciousTypes)) {
    echo '<br/><div class="alert-message warning">Check that the following classes implement either Parcelable or Serializable.<br/>'
    . 'Otherwise you\'ll get a RuntimeException.<br/><br/><ol>';
    
    foreach ($suspiciousTypes as $type) {
      echo '<li>' . $type . '</li>';
    }

    echo '</ol></div>';
  }
}

echo '<input type="hidden" name="fields" value="' . implode( ',', array_keys( $codeBuilder->getFields() ) ) . '" />
</div>
</div>
</fieldset>
<div class="actions">
  <input type="submit" name="submit" class="btn primary" value="Build" />
</div>
</form>';

if ( isset( $_POST['submit'] ) && ! $codeBuilder->getClass() ) {
  echo '<div class="alert-message info"><strong>Try again...</strong> We really do need the full class definition (including the class name) to build this. I promise, we won\'t steal your code.</div>';
} else if ( isset( $_POST['submit'] ) ) {
  $selectedFields = $_POST['field'];

  if ( count( $selectedFields ) > 0 ) {
    $code = htmlentities( $codeBuilder->getOutput( $selectedFields ) );

    echo '<h3>Output</h3><div class="alert-message success"><strong>Great news!</strong> Your code was parsed, you had fields for parceling, and the implementation for Parcelable is below.</div><p>Add the <a href="http://developer.android.com/reference/android/os/Parcelable.html">Parcelable</a> class to yours and add the following methods.</p><pre>' . $code . '</pre>';
  } else {
    echo '<div class="alert-message error"><strong>Oh noes :-/</strong> It looks like you don\'t have anything for parceling. Maybe it\'s my fault -- <a href="http://github.com/dallasgutauckis">let me know</a>.</div>';
  }
}

?>
  </div>
  </div>
  <script type="text/javascript">

   var _gaq = _gaq || [];
   _gaq.push(['_setAccount', 'UA-401905-5']);
   _gaq.push(['_trackPageview']);

   (function() {
     var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
     ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
     var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
   })();

  </script>
</body>
</html>
