/**
 * Save product form data with ajax
 */
$(function()
{
	$('#prodsave').click(function (){
		$.ajax({
            type: 'POST',
            data: 'format=json',
            url: '/ngo/enter-new-offering-info',
            async: false,
            success: function(rsp) {
                displayFeedback(rsp)
            }
		});
		return false;
	});
});

function displayFeedback(rsp){
	alert('feedback here');
}