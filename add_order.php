<div class="form-group">
    <label>Telefon</label>
    <input type="text" 
           name="phone" 
           class="form-control phone-input" 
           placeholder="90555 555 55 55"
           value="<?= htmlspecialchars($order['phone'] ?? '') ?>"
           required>
    <small class="form-text text-muted">Örnek: 90555 555 55 55</small>
</div> 