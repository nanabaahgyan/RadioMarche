<?php
/**
	echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>";
?>
<vxml version="2.0">

<form id="form_Main">

 <var name="callerID" expr="session.callerid"/>

 <prompt bargein="true">Welcome to the farmer market information platform.
	 <break />
 </prompt>
 
 <block>
     <submit next="read-product-offering.php" method="get" namelist="callerID"/>
 </block>
 </form>
</vxml> */