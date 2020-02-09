<?php
header('Content-type: application/xml');
echo file_get_contents('http://www.tcmb.gov.tr/kurlar/today.xml');