const ecommerce_enable_fields = document.getElementsByClassName("ngg_display_type_setting_ecommerce_marketing");
for (let i = 0; i < ecommerce_enable_fields.length; i++) {
    ecommerce_enable_fields[i].addEventListener('change', function(event) {
        this.removeAttribute('checked');
        const no_input = document.getElementById(this.id + '_no');
        no_input.setAttribute('checked', 'checked');
        no_input.checked = 'checked';

        alert(ngg_display_type_settings_marketing_i18n.alert);
    });
}
