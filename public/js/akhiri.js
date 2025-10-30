function showConfirmModal() {
    $('#confirmModal').css('display', 'block');
}

function hideConfirmModal() {
    $('#confirmModal').css('display', 'none');
}

function submitForm() {
    $('#akhiriForm').trigger('submit');
}

$(document).on('click', function(event) {
    const $modal = $('#confirmModal');
    if (event.target === $modal[0]) {
        hideConfirmModal();
    }
});

$(document).on('keydown', function(e) {
    if (e.key === 'Escape') {
        hideConfirmModal();
    }
});