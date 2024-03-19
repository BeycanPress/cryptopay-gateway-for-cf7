(($) => {
    $(document).ready(() => {
        const { __ } = window.wp.i18n
        const form = $(".wpcf7-form")
        const wpcf7 = document.querySelector('.wpcf7');
        const helpers = window.cpHelpers || window.cplHelpers

        const submit = `<input class="wpcf7-form-control wpcf7-submit has-spinner" type="submit" value="${__('Send')}"><span class="wpcf7-spinner"></span>`

        const transactionInput = (transaction) => {
            return `<input type="hidden" name="transaction-hash" value="${transaction.hash}" />`
        }

        wpcf7.addEventListener('wpcf7mailsent', async function (e) {
            await new Promise(resolve => setTimeout(resolve, 3000))
            form.find('input[type="submit"]').remove()
            window.location.reload()
        }, false);

        const event = async ({transaction}) => {
            helpers.closePopup();
            await helpers.sleep(100);
            $('.overlay').remove();
            form.append(transactionInput(transaction))
            $('#cryptopay, #cryptopay-lite').after(submit)
            $('#cryptopay, #cryptopay-lite').remove();
            form.find('input[type="submit"]').click()
            helpers.successPopup(__('Payment completed successfully!'))
        }

        if (window.CryptoPayApp) {
            window.CryptoPayApp.events.add('confirmationCompleted', event)
        } else if (window.CryptoPayLiteApp) {
            window.CryptoPayLiteApp.events.add('confirmationCompleted', event)
        }
    })
})(jQuery)