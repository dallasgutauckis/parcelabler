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
 * Implements a Date transformation of the given field
 */
class DateTransformer implements Transformer {
  public function getWriteCode( CodeField $field ) {
    return 'dest.writeLong(' . $field->getName() . ' != null ? ' . $field->getName() . '.getTime() : -1L);';
  }

  public function getReadCode( CodeField $field ) {
    $tmpFieldName = 'tmp' . ucwords( $field->getName() );
    $code  = 'long ' . $tmpFieldName . ' = in.readLong();' . "\n";
    $code .= $field->getName() . ' = ' . $tmpFieldName . ' != -1 ? new Date(' . $tmpFieldName . ') : null;';
    return $code;
  }
}
