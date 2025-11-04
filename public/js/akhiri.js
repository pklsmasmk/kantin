function showConfirmModal() {
    $('#confirmModal').addClass('show');
}

function hideConfirmModal() {
    $('#confirmModal').removeClass('show');
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