@props(['customModal' => '', 'baseBtn' => 'btn--primary', 'closeBtn' => 'btn--dark', 'sectionBg' => '','isFrontend' => false])
<div id="confirmationModal" class="modal {{ $customModal }} fade @if ($isFrontend) custom--modal @endif" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content {{ $sectionBg }}">
            <div class="modal-header">
                <h5 class="modal-title">@lang('Confirmation Alert!')</h5>
                <span class="close @if ($isFrontend) btn-close @endif" data-bs-dismiss="modal" type="button" aria-label="Close">
                    <i class="las la-times"></i>
                </span>
            </div>
            <form method="POST">
                @csrf
                <div class="modal-body">
                    <p class="question"></p>
                    {{$slot}}
                </div>
                <div class="modal-footer">
                    <button class="btn @if ($isFrontend) btn-dark btn--sm @else {{ $closeBtn }} @endif" data-bs-dismiss="modal" type="button">@lang('No')</button>
                    <button class="btn @if ($isFrontend) btn--base btn--sm @else {{ $baseBtn }} @endif" type="submit">@lang('Yes')</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('script')
    <script>
        (function($) {
            "use strict";
            $(document).on('click', '.confirmationBtn', function() {
                var modal = $('#confirmationModal');
                let data = $(this).data();
                modal.find('.question').text(`${data.question}`);
                modal.find('form').attr('action', `${data.action}`);
                if (data.modal_bg) {
                    modal.find('.modal-content').addClass(`${data.modal_bg}`);
                    modal.find('.submit-btn').addClass(`${data.btn_class}`);
                }
                modal.modal('show');
            });
        })(jQuery);
    </script>
@endpush
