<?php
/**
 * @var string $script_url
 * @var string $base_rest_url
 */
?>

<h1>NGG REST Debug</h1>

<style>
    #ngg-rest-debugger-parent td,
    #ngg-rest-debugger-parent th {
        border: 1px solid black;
    }
</style>

<div id="ngg-rest-debugger-parent"></div>

<script>
    window.ngg_rest_debugger_url = '<?php print esc_attr($base_rest_url); ?>';
</script>

<script src="<?php print esc_attr($script_url); ?>"></script>
<script type="text/javascript">
    window.initNGGRestDebugger(document.getElementById('ngg-rest-debugger-parent'));
</script>