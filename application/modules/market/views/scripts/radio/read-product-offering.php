<?php

	echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>";
	$file = APPLICATION_PATH . '/../public/product-offering.xml';
	$info = simplexml_load_file($file);
	
	$callerID = $_REQUEST["callerID"];
	
	header('Cache-Control: no-cache');
?>
<vxml version="2.0">
<form id="offering">
<block>
    <prompt bargein="true">Welcome to the farmer market information platform.
	 	<break />
   </prompt>
	<prompt>
		Your caller i d is:
		<say-as interpret-as="telephone">
		  <value expr="session.callerid"/>
		</say-as>
		<break/>
	</prompt>
	<prompt>
		<break/>
		The called i d of this application is:
        <say-as interpret-as="telephone">
          <value expr="session.calledid"/>
        </say-as>
        
        The caller id of this application is:
        <say-as interpret-as="telephone">
        	<value expr="session.callerid"/>
        </say-as>
	</prompt>
	
	<prompt>
			<break/>
			<?php
				 echo  $info->contactname;
			?>
			has
			<?php echo $info->quantity; ?>
			of
			<?php echo $info->name; ?> .
			<break />
	</prompt>
	<prompt>
			The price is
			<?php echo $info->price; ?>
			and the contact is
			<say-as interpret-as="telephone">
			  <?php
  					echo $info->contactphone;
			  ?>
			</say-as>
	</prompt>
</block>
</form>
</vxml>