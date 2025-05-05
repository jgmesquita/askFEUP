@php
    $icon = '';
    if ($type == 'success') $icon = 'check';
    elseif ($type == 'error') $icon = 'error';
    elseif ($type == 'info') $icon = 'info';
@endphp

<div class="{{ $type }} status-msg">
    <i class="material-icons">{{ $icon }}</i>
    <p>{{ $message }}</p>
</div>


<script>
    const statusMsg = document.querySelector('.status-msg');
    if (statusMsg) {
        setTimeout(() => {
            statusMsg.style.opacity = '0';
        }, 3500);
    }
</script>