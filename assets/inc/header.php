<?php
	/**
	 * Created by IntelliJ IDEA.
	 * User: Michael Risher
	 * Date: 5/22/2017
	 * Time: 10:08
	 */
	$language = Lang::getCode();
	$lang = new Lang( $language );
	if( MODE == 'live' ){
?>
<!-- Global Site Tag (gtag.js) - Google Analytics -->
<script async src="https://www.googletagmanager.com/gtag/js?id=UA-107370866-1"></script>
<script>
	window.dataLayer = window.dataLayer || [];
	function gtag(){dataLayer.push(arguments)};
	gtag('js', new Date());

	gtag('config', 'UA-107370866-1');
</script>
	<?php } ?>
<script>
var _bftn_options = {
    theme: 'slow', // @type {string}
  };
</script>
<!--<script src="https://widget.battleforthenet.com/widget.js" async></script>-->
<link rel="shortcut icon" href="<?=CORE_URL?>assets/img/favicon.ico" type="image/x-icon">
<link rel="icon" href="<?=CORE_URL?>assets/img/favicon.ico" type="image/x-icon">
<meta name="description" content="Pathways to degrees and certificates for Riverside City College">
<meta name="keywords" content="cscpathways,cispathways,riverside,city,college,pathways,computer,science,rcc">
<meta name="author" content="Michael Risher">
<title>CSC Pathways</title>