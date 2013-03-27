<?php
ini_set( 'error_reporting', 'on');
ini_set('display_errors', E_ALL );
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

$codeBuilder = new CodeBuilder( $file );

if ( $file && $codeBuilder->getClass() ) {
  $fields = $codeBuilder->getFields();

  foreach ( $fields as $field ) {
    $fieldName = $field->getName();
    $isChecked = '';
    $isSupported = $codeBuilder->isFieldSupported( $field );

    if ( $isSupported ) {
      $isChecked = count( $postedFields ) == 0 || false === in_array( $fieldName, $postedFields ) || ( isset( $_POST['field'][$fieldName] ) && !!$_POST['field'][$fieldName] );
    }

    echo '<li>' . ( $isSupported ? '' : '<span class="label warning">Unsupported type: ' . $field->getType() . '</span>' )
       . '<label>'
       . '<input type="checkbox" ' . ( $isChecked ? 'checked="checked"' : '' ) . 'name="field[' . $fieldName . ']" ' . ( $isSupported ? '' : 'disabled="disabled"' ) . ' />'
       . '<span>' . $fieldName . '</span></label></li>';

    $_POST['field'][$fieldName] = $isChecked;
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
