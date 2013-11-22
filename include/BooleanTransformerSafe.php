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
 * Implements a boolean transformation of the given field
 */
class BooleanTransformerSafe implements Transformer {
  public function getWriteCode( CodeField $field ) {
	$code  = 'if (' . $field->getName() . ' == null) {'. "\n";
	$code .= '    dest.writeByte((byte) (0x02));' . "\n";
	$code .= '} else {' . "\n";
	$code .= '    dest.writeByte((byte) (' . $field->getName() . ' ? 0x01 : 0x00));' . "\n";
	$code .= '}';
    return $code;
  }

  public function getReadCode( CodeField $field ) {
	$code  = 'byte ' . $field->getName() . 'Val = in.readByte();' . "\n";
	$code .= $field->getName() . ' = ' . $field->getName() . 'Val == 0x02 ? null : ' . $field->getName() . 'Val != 0x00;';
    return $code;
  }
}
