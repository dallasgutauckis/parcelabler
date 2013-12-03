<?php
/*
 * Copyright 2013 MeetMe, Inc.
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
 * Implements a straightforward transformation of the given field 
 */
class IntegratedTransformerSafe implements Transformer {
  private $mTypeSuffix;

  public function __construct( $typeSuffix ) {
    $this->mTypeSuffix = $typeSuffix;
  }

  public function getWriteCode( CodeField $field ) {
	$code  = 'if (' . $field->getName() . ' == null) {'. "\n";
	$code .= '    dest.writeByte((byte) (0x00));' . "\n";
	$code .= '} else {' . "\n";
	$code .= '    dest.writeByte((byte) (0x01));' . "\n";
	$code .= '    dest.write' . $this->mTypeSuffix . '(' . $field->getName() . ');' . "\n";
	$code .= '}';
    return $code;
  }

  public function getReadCode( CodeField $field ) {
    return $field->getName() . ' = in.readByte() == 0x00 ? null : in.read' . $this->mTypeSuffix . '();';
  }
}
