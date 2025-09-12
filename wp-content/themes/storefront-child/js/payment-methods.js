(function($){
    // Modal open/close
    function openModal($scope, text){
      $scope.find('.dp-status-pre').text(typeof text === 'string' ? text : JSON.stringify(text, null, 2));
      $scope.show();
    }
    function closeModal($scope){ $scope.hide(); }
  
    // Cerrar modal
    $(document).on('click', '.dp-modal-close', function(e){
      e.preventDefault();
      closeModal($(this).closest('#dp-status-modal'));
    });
  
    // Revisar pago (frontend + admin)
    $(document).on('click', '.dp-check-status', function(e){
      e.preventDefault();
  
      var $btn    = $(this);
      var orderId = $btn.data('order-id');
  
      // Scope + modal (busca dentro de sección, metabox o body)
      var $scope = $btn.closest('section, .postbox, body');
      var $modal = $scope.find('#dp-status-modal');
      if (!$modal.length) { $modal = $('#dp-status-modal'); }
  
      // Lugar para resultado corto en lista de pedidos (si existe)
      var $inline = $btn.closest('td, .postbox, section, body').find('.dp-inline-result').first();
  
      // UX: estado de carga en el botón
      var originalText = $btn.text();
      $btn.prop('disabled', true).text('Revisando…');
  
      $.post(DP_AJAX.url, {
        action: 'dp_check_payment',
        _ajax_nonce: DP_AJAX.nonce,
        order_id: orderId
      }).done(function(res){
        if(res && res.success){
          var data = res.data && res.data.data ? res.data.data : res.data;
          var paid = (data.amountCaptured && Number(data.amountCaptured) > 0);
          var msg  = (paid ? 'Pago recibido ✅\n\n' : 'Aún en espera ⏳\n\n') + JSON.stringify(data, null, 2);
  
          if($inline.length){ $inline.text(paid ? 'Pago recibido ✅' : 'En espera ⏳'); }
          openModal($modal, msg);
        } else {
          var err = 'Error: ' + (res && res.data && res.data.message ? res.data.message : 'Desconocido');
          if($inline.length){ $inline.text(err); }
          openModal($modal, err);
        }
      }).fail(function(){
        if($inline.length){ $inline.text('No se pudo conectar.'); }
        openModal($modal, 'No se pudo conectar. Intenta de nuevo.');
      }).always(function(){
        $btn.prop('disabled', false).text(originalText);
      });
    });
  })(jQuery);
  