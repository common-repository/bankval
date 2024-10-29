<?php
    $action = 'bankval_form';
?>

<form id="bankval-form" method="post" >
    <?php wp_nonce_field($action);?>

    <div class="response-group">
        <span id="message" class="response-element"></span>
        <span id="closeButton" class="response-element">&times;</span>
    </div>

    <div class="input-group">
        <label for="sortcode">Sort code</label>
        <input
            id="sortcode"
            name="sortcode"
            type="tel"
            inputmode="numeric"
            maxlength="8"
            pattern="[0-9]{6}"
            title="6 digit number"
            required
        />
    </div>

    <div class="input-group">
        <label for="accno">Account number</label>
        <input
            id="accno"
            name="accno"
            type="tel"
            inputmode="numeric"
            maxlength="8"
            pattern="[0-9]{7,8}"
            title="7-8 digit number"
            required
        />
    </div>
    
    <div class="input-group submit-group">
        <button type="submit">Validate</button>
    </div>

    <?php if ( array_key_exists( 'misc_powered_by', get_option('unified_software_bankval') ) ) { ?>
        <div class="input-group">
            <span id="powered-by-bankval">Powered By <a href="https://www.unifiedsoftware.co.uk/blog/how-does-bankval-work/">BankVal</a> &reg;</span>
        </div>
    <?php } ?>


</form>