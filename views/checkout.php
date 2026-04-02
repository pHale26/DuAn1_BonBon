<script>
let appliedCoupon = null;
let currentDiscount = 0;
let originalTotal = 0;

// Tính tổng tiền ban đầu khi trang load
document.addEventListener('DOMContentLoaded', function() {
    const subtotalElement = document.getElementById('subtotalAmount');
    if (subtotalElement) {
        const subtotalText = subtotalElement.textContent.replace(/[^\d]/g, '');
        originalTotal = parseFloat(subtotalText) || 0;
    }
    
    // Chỉ khởi tạo coupon functions nếu có form coupon
    const couponCodeInput = document.getElementById('couponCode');
    if (!couponCodeInput) {
        // Trang checkout không có form nhập coupon, chỉ hiển thị coupon đã áp dụng
        console.log('Checkout page: No coupon input form found');
    }
});

function applyCoupon() {
    const couponCodeInput = document.getElementById('couponCode');
    if (!couponCodeInput) {
        console.warn('Coupon code input not found');
        return;
    }
    
    const code = couponCodeInput.value.trim().toUpperCase();
    const messageDiv = document.getElementById('couponMessage');
    const applyBtn = document.getElementById('applyCouponBtn');
    
    if (!messageDiv || !applyBtn) {
        console.warn('Coupon message or button not found');
        return;
    }
    
    if (!code) {
        messageDiv.innerHTML = '<span class="coupon-message error">Vui lòng nhập mã giảm giá</span>';
        return;
    }
    
    applyBtn.disabled = true;
    applyBtn.textContent = 'Đang kiểm tra...';
    messageDiv.innerHTML = '';
    
    fetch('<?= BASE_URL ?>?action=coupon-validate', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            coupon_code: code,
            order_amount: originalTotal
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            appliedCoupon = data.coupon;
            currentDiscount = data.discount_amount;
            
            // Cập nhật UI
            document.getElementById('appliedCouponId').value = data.coupon.id;
            document.getElementById('appliedCouponCode').value = data.coupon.code;
            document.getElementById('discountAmount').value = currentDiscount;
            
            // Hiển thị thông báo
            messageDiv.innerHTML = `<span class="coupon-message success">✓ ${data.message}</span>`;
            
            // Cập nhật tổng tiền
            updateTotals();
            
            // Disable input và button
            if (couponCodeInput) {
                couponCodeInput.disabled = true;
            }
            applyBtn.textContent = 'Đã áp dụng';
            applyBtn.disabled = true;
            applyBtn.classList.remove('btn-outline-dark');
            applyBtn.classList.add('btn-dark');
            
            // Thêm nút xóa mã
            if (!document.getElementById('removeCouponBtn')) {
                const removeBtn = document.createElement('button');
                removeBtn.type = 'button';
                removeBtn.className = 'btn btn-sm btn-outline-danger mt-2';
                removeBtn.id = 'removeCouponBtn';
                removeBtn.textContent = 'Xóa mã';
                removeBtn.onclick = removeCoupon;
                messageDiv.appendChild(document.createElement('br'));
                messageDiv.appendChild(removeBtn);
            }
        } else {
            messageDiv.innerHTML = `<span class="coupon-message error">${data.message}</span>`;
            applyBtn.disabled = false;
            applyBtn.textContent = 'Áp dụng';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        messageDiv.innerHTML = '<span class="coupon-message error">Có lỗi xảy ra. Vui lòng thử lại.</span>';
        applyBtn.disabled = false;
        applyBtn.textContent = 'Áp dụng';
    });
}

function removeCoupon() {
    appliedCoupon = null;
    currentDiscount = 0;
    
    const appliedCouponId = document.getElementById('appliedCouponId');
    const appliedCouponCode = document.getElementById('appliedCouponCode');
    const discountAmount = document.getElementById('discountAmount');
    const couponCodeInput = document.getElementById('couponCode');
    const couponMessage = document.getElementById('couponMessage');
    
    if (appliedCouponId) appliedCouponId.value = '';
    if (appliedCouponCode) appliedCouponCode.value = '';
    if (discountAmount) discountAmount.value = '0';
    if (couponCodeInput) {
        couponCodeInput.value = '';
        couponCodeInput.disabled = false;
    }
    if (couponMessage) couponMessage.innerHTML = '';
    
    const applyBtn = document.getElementById('applyCouponBtn');
    if (applyBtn) {
        applyBtn.disabled = false;
        applyBtn.textContent = 'Áp dụng';
        applyBtn.classList.remove('btn-dark');
        applyBtn.classList.add('btn-outline-dark');
    }
    
    const removeBtn = document.getElementById('removeCouponBtn');
    if (removeBtn) {
        removeBtn.remove();
    }
    
    updateTotals();
}

function updateTotals() {
    const finalTotal = originalTotal - currentDiscount;
    
    const subtotalElement = document.getElementById('subtotalAmount');
    const discountRow = document.getElementById('discountRow');
    const discountAmountDisplay = document.getElementById('discountAmountDisplay');
    const finalTotalElement = document.getElementById('finalTotalAmount');
    
    if (subtotalElement) subtotalElement.textContent = formatCurrency(originalTotal);
    
    if (currentDiscount > 0) {
        if (discountRow) discountRow.style.display = 'flex';
        if (discountAmountDisplay) discountAmountDisplay.textContent = '-' + formatCurrency(currentDiscount);
    } else {
        if (discountRow) discountRow.style.display = 'none';
    }
    
    if (finalTotalElement) finalTotalElement.textContent = formatCurrency(finalTotal);
}

function formatCurrency(amount) {
    return new Intl.NumberFormat('vi-VN').format(Math.round(amount)) + ' đ';
}

// Cho phép nhấn Enter để áp dụng mã (chỉ nếu element tồn tại)
const couponCodeInput = document.getElementById('couponCode');
if (couponCodeInput) {
    couponCodeInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            applyCoupon();
        }
    });
}

// Chọn địa chỉ từ danh sách
function selectAddress(address, element) {
    document.getElementById('fullname').value = address.fullname || '';
    document.getElementById('phone').value = address.phone || '';
    document.getElementById('email').value = address.email || '';
    document.getElementById('address').value = address.address || '';
    document.getElementById('city').value = address.city || '';
    document.getElementById('district').value = address.district || '';
    document.getElementById('ward').value = address.ward || '';
    
    // Highlight địa chỉ được chọn
    document.querySelectorAll('.address-item').forEach(item => {
        item.style.borderColor = '';
        item.style.backgroundColor = '';
    });
    if (element) {
        element.style.borderColor = '#000';
        element.style.backgroundColor = '#f8f9fa';
    }
}

// Đặt địa chỉ làm mặc định
function setDefaultAddress(addressId) {
    if (!confirm('Bạn có muốn đặt địa chỉ này làm mặc định không?')) {
        return;
    }
    
    fetch('<?= BASE_URL ?>?action=set-default-address', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            address_id: addressId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Đã đặt địa chỉ làm mặc định thành công!');
            location.reload();
        } else {
            alert('Có lỗi xảy ra: ' + (data.message || ''));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Có lỗi xảy ra. Vui lòng thử lại.');
    });
}

// Hiển thị form nhập địa chỉ mới
function showNewAddressForm() {
    // Xóa giá trị các trường
    document.getElementById('fullname').value = '';
    document.getElementById('phone').value = '';
    document.getElementById('email').value = '';
    document.getElementById('address').value = '';
    document.getElementById('city').value = '';
    document.getElementById('district').value = '';
    document.getElementById('ward').value = '';
    
    // Bỏ highlight các địa chỉ
    document.querySelectorAll('.address-item').forEach(item => {
        item.style.borderColor = '';
        item.style.backgroundColor = '';
    });
    
    // Scroll đến form
    document.getElementById('addressForm').scrollIntoView({ behavior: 'smooth', block: 'start' });
}

// Chọn địa chỉ từ modal
let selectedAddressId = null;
function selectAddressFromModal(addressId, element) {
    selectedAddressId = addressId;
    
    // Kiểm tra element có tồn tại không
    if (!element) {
        console.error('Element is null in selectAddressFromModal');
        return;
    }
    
    // Update radio button
    document.querySelectorAll('input[name="selectedAddress"]').forEach(radio => {
        radio.checked = false;
    });
    
    // Tìm radio button trong element hoặc parent element
    let radio = null;
    if (element.querySelector) {
        radio = element.querySelector('input[type="radio"]');
    }
    if (!radio && element.closest) {
        const parentElement = element.closest('.address-item-modal');
        if (parentElement) {
            radio = parentElement.querySelector('input[type="radio"]');
        }
    }
    if (radio) {
        radio.checked = true;
    }
    
    // Update UI
    document.querySelectorAll('.address-item-modal').forEach(item => {
        item.classList.remove('selected');
    });
    
    const addressItem = element.closest ? element.closest('.address-item-modal') : element;
    if (addressItem) {
        addressItem.classList.add('selected');
    }
}

// Xác nhận chọn địa chỉ
function confirmAddressSelection() {
    const checkedRadio = document.querySelector('input[name="selectedAddress"]:checked');
    if (!checkedRadio) {
        alert('Vui lòng chọn một địa chỉ');
        return;
    }
    
    selectedAddressId = parseInt(checkedRadio.value);
    
    // Tìm địa chỉ được chọn
    const addresses = <?= json_encode($userAddresses ?? []) ?>;
    const selectedAddress = addresses.find(addr => addr.address_id == selectedAddressId);
    
    if (selectedAddress) {
        // Điền vào form checkout
        document.getElementById('fullname').value = selectedAddress.fullname || '';
        document.getElementById('phone').value = selectedAddress.phone || '';
        document.getElementById('email').value = selectedAddress.email || '';
        document.getElementById('address').value = selectedAddress.address || '';
        document.getElementById('city').value = selectedAddress.city || '';
        document.getElementById('district').value = selectedAddress.district || '';
        document.getElementById('ward').value = selectedAddress.ward || '';
        
        // Hiển thị form nếu đang ẩn
        document.getElementById('addressForm').style.display = 'block';
        
        // Đóng modal
        const modal = bootstrap.Modal.getInstance(document.getElementById('addressModal'));
        modal.hide();
        
        // Reload để cập nhật hiển thị địa chỉ mặc định
        location.reload();
    }
}

// Mở modal thêm/sửa địa chỉ
function openAddressFormModal(addressId = null) {
    const modalElement = document.getElementById('addressFormModal');
    const modal = bootstrap.Modal.getOrCreateInstance(modalElement);
    
    // Đăng ký event listener khi modal được mở
    modalElement.addEventListener('shown.bs.modal', function onModalShown() {
        // Chỉ chạy một lần
        modalElement.removeEventListener('shown.bs.modal', onModalShown);
        
        // Đăng ký lại event listener cho ô tìm kiếm
        setTimeout(() => {
            setupAddressSearch();
            // Load danh sách tỉnh nếu chưa có
            if (currentTab === 'province' && (!provinces || provinces.length === 0)) {
                loadProvinces();
            }
        }, 100);
    }, { once: true });
    
    // Reset form
    resetAddressForm();
    
    if (addressId) {
        // Sửa địa chỉ
        document.getElementById('addressFormModalLabel').textContent = 'Sửa địa chỉ';
        const addresses = <?= json_encode($userAddresses ?? []) ?>;
        const address = addresses.find(addr => addr.address_id == addressId);
        if (address) {
            populateAddressForm(address);
        }
    } else {
        // Thêm mới - điền từ địa chỉ hiện tại nếu có
        document.getElementById('addressFormModalLabel').textContent = 'Địa chỉ mới';
        const currentAddress = <?= json_encode($defaultAddress ?? []) ?>;
        if (currentAddress && Object.keys(currentAddress).length > 0) {
            populateAddressForm(currentAddress);
        }
    }
    
    // Load provinces khi mở modal
    loadProvinces();
    
    modal.show();
}

// Điền dữ liệu vào form
function populateAddressForm(address) {
    document.getElementById('formFullname').value = address.fullname || '';
    document.getElementById('formPhone').value = address.phone || '';
    document.getElementById('formEmail').value = address.email || '';
    document.getElementById('formAddress').value = address.address || '';
    document.getElementById('formCity').value = address.city || '';
    document.getElementById('formCityCode').value = '';
    document.getElementById('formDistrict').value = address.district || '';
    document.getElementById('formDistrictCode').value = '';
    document.getElementById('formWard').value = address.ward || '';
    document.getElementById('formWardCode').value = '';
    document.getElementById('formAddressId').value = address.address_id || '';
    
    // Set address type
    if (address.address_type === 'office') {
        selectAddressType('office');
    } else {
        selectAddressType('home');
    }
    
    // Set default checkbox
    document.getElementById('setAsDefault').checked = address.is_default == 1;
    
    // Nếu có địa chỉ, tìm và chọn trong danh sách
    if (address.city) {
        searchAndSelectAddressFromForm(address.city, address.district, address.ward);
    }
}

// Reset form
function resetAddressForm() {
    document.getElementById('formFullname').value = '';
    document.getElementById('formPhone').value = '';
    document.getElementById('formEmail').value = '';
    document.getElementById('formAddress').value = '';
    document.getElementById('formCity').value = '';
    document.getElementById('formCityCode').value = '';
    document.getElementById('formDistrict').value = '';
    document.getElementById('formDistrictCode').value = '';
    document.getElementById('formWard').value = '';
    document.getElementById('formWardCode').value = '';
    document.getElementById('formAddressId').value = '';
    document.getElementById('addressSearchInput').value = '';
    document.getElementById('setAsDefault').checked = false;
    document.getElementById('selectedAddressDisplay').style.display = 'none';
    
    // Reset address selection - Kiểm tra biến đã được khai báo chưa
    if (typeof selectedProvince !== 'undefined') {
        selectedProvince = null;
    }
    if (typeof selectedDistrict !== 'undefined') {
        selectedDistrict = null;
    }
    if (typeof selectedWard !== 'undefined') {
        selectedWard = null;
    }
    
    // Reset tabs
    switchAddressTab('province');
    
    // Clear selected items
    document.querySelectorAll('.address-list-item').forEach(el => {
        el.classList.remove('selected');
    });
}

// Tìm và chọn địa chỉ từ form
function searchAndSelectAddressFromForm(provinceName, districtName, wardName) {
    if (!provinceName) return;
    
    // Tìm tỉnh - tìm chính xác hoặc gần đúng
    fetch(`<?= BASE_URL ?>?action=address-search&keyword=${encodeURIComponent(provinceName)}`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.results.length > 0) {
                // Tìm tỉnh khớp chính xác nhất
                let provinceResult = data.results.find(r => r.type === 'province' && r.name.toLowerCase() === provinceName.toLowerCase());
                if (!provinceResult) {
                    provinceResult = data.results.find(r => r.type === 'province');
                }
                
                if (provinceResult) {
                    selectSearchResult(provinceResult);
                    
                    // Tìm quận/huyện
                    if (districtName) {
                        setTimeout(() => {
                            fetch(`<?= BASE_URL ?>?action=address-search&keyword=${encodeURIComponent(districtName)}`)
                                .then(response => response.json())
                                .then(data => {
                                    if (data.success && data.results.length > 0) {
                                        // Tìm quận/huyện khớp chính xác nhất trong tỉnh đã chọn
                                        let districtResult = data.results.find(r => 
                                            r.type === 'district' && 
                                            r.province_code === provinceResult.code &&
                                            r.name.toLowerCase() === districtName.toLowerCase()
                                        );
                                        if (!districtResult) {
                                            districtResult = data.results.find(r => 
                                                r.type === 'district' && 
                                                r.province_code === provinceResult.code
                                            );
                                        }
                                        
                                        if (districtResult) {
                                            selectSearchResult(districtResult);
                                            
                                            // Tìm phường/xã
                                            if (wardName) {
                                                setTimeout(() => {
                                                    fetch(`<?= BASE_URL ?>?action=address-search&keyword=${encodeURIComponent(wardName)}`)
                                                        .then(response => response.json())
                                                        .then(data => {
                                                            if (data.success && data.results.length > 0) {
                                                                // Tìm phường/xã khớp chính xác nhất trong quận/huyện đã chọn
                                                                let wardResult = data.results.find(r => 
                                                                    r.type === 'ward' && 
                                                                    r.district_code === districtResult.code &&
                                                                    r.name.toLowerCase() === wardName.toLowerCase()
                                                                );
                                                                if (!wardResult) {
                                                                    wardResult = data.results.find(r => 
                                                                        r.type === 'ward' && 
                                                                        r.district_code === districtResult.code
                                                                    );
                                                                }
                                                                
                                                                if (wardResult) {
                                                                    selectSearchResult(wardResult);
                                                                }
                                                            }
                                                        });
                                                }, 800);
                                            }
                                        }
                                    }
                                });
                        }, 800);
                    }
                }
            }
        })
        .catch(error => {
            console.error('Error searching address:', error);
        });
}

// Chọn loại địa chỉ
function selectAddressType(type) {
    document.querySelectorAll('.address-type-btn').forEach(btn => {
        btn.classList.remove('active');
    });
    event.currentTarget.classList.add('active');
    document.getElementById('addressType').value = type;
}

// Load provinces khi mở modal
document.addEventListener('DOMContentLoaded', function() {
    const addressModal = document.getElementById('addressFormModal');
    if (addressModal) {
        addressModal.addEventListener('hidden.bs.modal', function() {
            // Reset form khi đóng modal
            resetAddressForm();
        });
    }
    
});

// Load danh sách tỉnh
function loadProvinces() {
    const container = document.getElementById('addressList');
    container.innerHTML = '<div class="text-center py-3"><div class="spinner-border spinner-border-sm" role="status"><span class="visually-hidden">Loading...</span></div></div>';
    
    fetch('<?= BASE_URL ?>?action=address-provinces')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                provinces = data.provinces;
                // Kiểm tra xem có từ khóa tìm kiếm không
                const searchInput = document.getElementById('addressSearchInput');
                const keyword = searchInput ? searchInput.value.trim() : '';
                if (keyword.length >= 1) {
                    // Nếu có từ khóa, lọc danh sách
                    filterAddressList(keyword);
                } else {
                    // Nếu không có từ khóa, hiển thị toàn bộ
                    displayAddressList(data.provinces, 'province');
                }
            } else {
                container.innerHTML = '<div class="text-center py-3 text-muted">Không thể tải danh sách tỉnh/thành phố</div>';
            }
        })
        .catch(error => {
            console.error('Error loading provinces:', error);
            container.innerHTML = '<div class="text-center py-3 text-danger">Có lỗi xảy ra khi tải dữ liệu</div>';
        });
}

// Load danh sách quận/huyện
function loadDistricts(provinceCode) {
    const container = document.getElementById('addressList');
    container.innerHTML = '<div class="text-center py-3"><div class="spinner-border spinner-border-sm" role="status"><span class="visually-hidden">Loading...</span></div></div>';
    
    fetch(`<?= BASE_URL ?>?action=address-districts&province_code=${provinceCode}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                districts = data.districts;
                if (districts.length > 0) {
                    // Kiểm tra xem có từ khóa tìm kiếm không
                    const searchInput = document.getElementById('addressSearchInput');
                    const keyword = searchInput ? searchInput.value.trim() : '';
                    if (keyword.length >= 1) {
                        // Nếu có từ khóa, lọc danh sách
                        filterAddressList(keyword);
                    } else {
                        // Nếu không có từ khóa, hiển thị toàn bộ
                        displayAddressList(data.districts, 'district');
                    }
                } else {
                    container.innerHTML = '<div class="text-center py-3 text-muted">Không có quận/huyện nào</div>';
                }
            } else {
                container.innerHTML = '<div class="text-center py-3 text-muted">Không thể tải danh sách quận/huyện</div>';
            }
        })
        .catch(error => {
            console.error('Error loading districts:', error);
            container.innerHTML = '<div class="text-center py-3 text-danger">Có lỗi xảy ra khi tải dữ liệu</div>';
        });
}

// Load danh sách phường/xã
function loadWards(districtCode) {
    const container = document.getElementById('addressList');
    container.innerHTML = '<div class="text-center py-3"><div class="spinner-border spinner-border-sm" role="status"><span class="visually-hidden">Loading...</span></div></div>';
    
    fetch(`<?= BASE_URL ?>?action=address-wards&district_code=${districtCode}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                wards = data.wards;
                if (wards.length > 0) {
                    // Kiểm tra xem có từ khóa tìm kiếm không
                    const searchInput = document.getElementById('addressSearchInput');
                    const keyword = searchInput ? searchInput.value.trim() : '';
                    if (keyword.length >= 1) {
                        // Nếu có từ khóa, lọc danh sách
                        filterAddressList(keyword);
                    } else {
                        // Nếu không có từ khóa, hiển thị toàn bộ
                        displayAddressList(data.wards, 'ward');
                    }
                } else {
                    container.innerHTML = '<div class="text-center py-3 text-muted">Không có phường/xã nào</div>';
                }
            } else {
                container.innerHTML = '<div class="text-center py-3 text-muted">Không thể tải danh sách phường/xã</div>';
            }
        })
        .catch(error => {
            console.error('Error loading wards:', error);
            container.innerHTML = '<div class="text-center py-3 text-danger">Có lỗi xảy ra khi tải dữ liệu</div>';
        });
}

// Hiển thị danh sách địa chỉ
function displayAddressList(items, type) {
    const container = document.getElementById('addressList');
    container.innerHTML = '';
    
    if (!items || items.length === 0) {
        container.innerHTML = '<div class="text-center py-3 text-muted">Không có dữ liệu</div>';
        return;
    }
    
    items.forEach(item => {
        const div = document.createElement('div');
        div.className = 'address-list-item';
        div.textContent = item.name;
        div.onclick = function() { selectAddressItem(item, type, this); };
        container.appendChild(div);
    });
}

// Cập nhật địa chỉ cụ thể dựa trên tỉnh/quận/xã đã chọn
function updateFormAddress() {
    const addressParts = [];
    
    if (selectedWard && selectedWard.name) {
        addressParts.push(selectedWard.name);
    }
    if (selectedDistrict && selectedDistrict.name) {
        addressParts.push(selectedDistrict.name);
    }
    if (selectedProvince && selectedProvince.name) {
        addressParts.push(selectedProvince.name);
    }
    
    const formAddress = document.getElementById('formAddress');
    if (!formAddress) return;
    
    // Lấy địa chỉ hiện tại
    const currentAddress = formAddress.value.trim();
    
    if (!currentAddress) {
        // Nếu địa chỉ rỗng, chỉ điền tỉnh/quận/xã
        if (addressParts.length > 0) {
            formAddress.value = addressParts.join(', ');
        }
        return;
    }
    
    // Tách địa chỉ thành các phần
    let addressPartsArray = currentAddress.split(',').map(part => part.trim());
    
    // Lấy danh sách tất cả tỉnh/quận/xã để loại bỏ khỏi địa chỉ
    const allLocationNames = [];
    if (provinces && provinces.length > 0) {
        provinces.forEach(p => allLocationNames.push(p.name));
    }
    if (districts && districts.length > 0) {
        districts.forEach(d => allLocationNames.push(d.name));
    }
    if (wards && wards.length > 0) {
        wards.forEach(w => allLocationNames.push(w.name));
    }
    
    // Thêm các tỉnh/quận/xã đã chọn trước đó vào danh sách để loại bỏ
    if (selectedProvince && selectedProvince.name) {
        allLocationNames.push(selectedProvince.name);
    }
    if (selectedDistrict && selectedDistrict.name) {
        allLocationNames.push(selectedDistrict.name);
    }
    if (selectedWard && selectedWard.name) {
        allLocationNames.push(selectedWard.name);
    }
    
    // Loại bỏ các phần là tỉnh/quận/xã (giữ lại địa chỉ cụ thể như số nhà, tên đường)
    const specificAddressParts = addressPartsArray.filter(part => {
        if (!part) return false;
        // Kiểm tra xem phần này có phải là tên tỉnh/quận/xã không
        const isLocation = allLocationNames.some(locationName => {
            const partLower = part.toLowerCase();
            const locationLower = locationName.toLowerCase();
            return partLower === locationLower || 
                   partLower.includes(locationLower) || 
                   locationLower.includes(partLower);
        });
        return !isLocation;
    });
    
    // Lấy địa chỉ cụ thể (số nhà, tên đường)
    const specificAddress = specificAddressParts.join(', ').trim();
    
    // Tạo địa chỉ mới: địa chỉ cụ thể + tỉnh/quận/xã mới
    if (addressParts.length > 0) {
        if (specificAddress) {
            formAddress.value = specificAddress + ', ' + addressParts.join(', ');
        } else {
            formAddress.value = addressParts.join(', ');
        }
    } else if (specificAddress) {
        // Nếu không có tỉnh/quận/xã nhưng có địa chỉ cụ thể, giữ lại
        formAddress.value = specificAddress;
    } else {
        // Nếu không có gì, để trống
        formAddress.value = '';
    }
}

// Chọn địa chỉ từ danh sách
function selectAddressItem(item, type, element) {
    // Remove selected class from all items
    document.querySelectorAll('.address-list-item').forEach(el => {
        el.classList.remove('selected');
    });
    
    // Add selected class to clicked item
    if (element) {
        element.classList.add('selected');
    } else if (event && event.currentTarget) {
        event.currentTarget.classList.add('selected');
    }
    
    if (type === 'province') {
        selectedProvince = item;
        document.getElementById('formCity').value = item.name;
        document.getElementById('formCityCode').value = item.code;
        // Reset district và ward khi chọn tỉnh mới
        selectedDistrict = null;
        selectedWard = null;
        document.getElementById('formDistrict').value = '';
        document.getElementById('formDistrictCode').value = '';
        document.getElementById('formWard').value = '';
        document.getElementById('formWardCode').value = '';
        
        // Xóa thanh tìm kiếm
        const searchInput = document.getElementById('addressSearchInput');
        if (searchInput) {
            searchInput.value = '';
        }
        
        // Cập nhật địa chỉ cụ thể
        updateFormAddress();
        
        loadDistricts(item.code);
        switchAddressTab('district');
        updateSelectedAddressDisplay();
    } else if (type === 'district') {
        selectedDistrict = item;
        document.getElementById('formDistrict').value = item.name;
        document.getElementById('formDistrictCode').value = item.code;
        // Reset ward khi chọn quận/huyện mới
        selectedWard = null;
        document.getElementById('formWard').value = '';
        document.getElementById('formWardCode').value = '';
        
        // Xóa thanh tìm kiếm
        const searchInput = document.getElementById('addressSearchInput');
        if (searchInput) {
            searchInput.value = '';
        }
        
        // Cập nhật địa chỉ cụ thể
        updateFormAddress();
        
        loadWards(item.code);
        switchAddressTab('ward');
        updateSelectedAddressDisplay();
    } else if (type === 'ward') {
        selectedWard = item;
        document.getElementById('formWard').value = item.name;
        document.getElementById('formWardCode').value = item.code;
        
        // Xóa thanh tìm kiếm
        const searchInput = document.getElementById('addressSearchInput');
        if (searchInput) {
            searchInput.value = '';
        }
        
        // Cập nhật địa chỉ cụ thể
        updateFormAddress();
        
        updateSelectedAddressDisplay();
    }
}

// Chuyển tab
function switchAddressTab(tab) {
    currentTab = tab;
    
    // Update tabs
    document.querySelectorAll('.address-tab').forEach(t => {
        t.classList.remove('active');
        if (t.dataset.tab === tab) {
            t.classList.add('active');
        }
    });
    
    // Enable/disable tabs
    const provinceTab = document.querySelector('.address-tab[data-tab="province"]');
    const districtTab = document.querySelector('.address-tab[data-tab="district"]');
    const wardTab = document.querySelector('.address-tab[data-tab="ward"]');
    
    if (tab === 'province') {
        districtTab.disabled = true;
        wardTab.disabled = true;
        displayAddressList(provinces, 'province');
    } else if (tab === 'district') {
        districtTab.disabled = false;
        wardTab.disabled = true;
        if (selectedProvince) {
            displayAddressList(districts, 'district');
        }
    } else if (tab === 'ward') {
        districtTab.disabled = false;
        wardTab.disabled = false;
        if (selectedDistrict) {
            displayAddressList(wards, 'ward');
        }
    }
}

// Cập nhật hiển thị địa chỉ đã chọn
function updateSelectedAddressDisplay() {
    const parts = [];
    if (selectedWard) parts.push(selectedWard.name);
    if (selectedDistrict) parts.push(selectedDistrict.name);
    if (selectedProvince) parts.push(selectedProvince.name);
    
    if (parts.length > 0) {
        document.getElementById('selectedAddressText').textContent = parts.join(', ');
        document.getElementById('selectedAddressDisplay').style.display = 'flex';
        // KHÔNG tự động điền vào thanh tìm kiếm để người dùng có thể tìm tiếp
    } else {
        document.getElementById('selectedAddressDisplay').style.display = 'none';
    }
}

// Xóa địa chỉ đã chọn
function clearSelectedAddress() {
    selectedProvince = null;
    selectedDistrict = null;
    selectedWard = null;
    document.getElementById('formCity').value = '';
    document.getElementById('formCityCode').value = '';
    document.getElementById('formDistrict').value = '';
    document.getElementById('formDistrictCode').value = '';
    document.getElementById('formWard').value = '';
    document.getElementById('formWardCode').value = '';
    document.getElementById('selectedAddressDisplay').style.display = 'none';
    document.querySelectorAll('.address-list-item').forEach(el => {
        el.classList.remove('selected');
    });
    switchAddressTab('province');
}

// Tìm kiếm địa chỉ
// searchTimeout đã được khai báo ở đầu script

// Hàm để đăng ký event listener cho ô tìm kiếm
function setupAddressSearch() {
    const searchInput = document.getElementById('addressSearchInput');
    if (!searchInput) {
        // Nếu chưa có, thử lại sau 100ms (modal có thể chưa mở)
        setTimeout(setupAddressSearch, 100);
        return;
    }
    
    // Xóa event listener cũ nếu có
    const newSearchInput = searchInput.cloneNode(true);
    searchInput.parentNode.replaceChild(newSearchInput, searchInput);
    
    // Đăng ký event listener mới
    newSearchInput.addEventListener('input', function(e) {
        const keyword = e.target.value.trim();
        
        clearTimeout(searchTimeout);
        
        // Ẩn dropdown search results
        const searchResults = document.getElementById('addressSearchResults');
        if (searchResults) {
            searchResults.classList.remove('show');
        }
        
        // Lọc danh sách địa chỉ hiện tại ngay lập tức
        if (keyword.length >= 1) {
            searchTimeout = setTimeout(() => {
                filterAddressList(keyword);
            }, 100); // Giảm delay để phản hồi nhanh hơn
        } else {
            // Nếu xóa hết từ khóa, hiển thị lại toàn bộ danh sách
            restoreAddressList();
        }
    });
    
    // Xử lý khi focus vào ô tìm kiếm
    newSearchInput.addEventListener('focus', function() {
        const keyword = this.value.trim();
        if (keyword.length >= 1) {
            filterAddressList(keyword);
        }
    });
}

// Đăng ký khi DOM ready
document.addEventListener('DOMContentLoaded', function() {
    setupAddressSearch();
});

// Đăng ký lại khi modal được mở
document.addEventListener('shown.bs.modal', function(e) {
    if (e.target && e.target.id === 'addressFormModal') {
        setTimeout(setupAddressSearch, 100);
    }
});

// Lọc danh sách địa chỉ hiện tại
function filterAddressList(keyword) {
    const container = document.getElementById('addressList');
    if (!container) {
        return;
    }
    
    // Đảm bảo currentTab được xác định đúng
    if (!currentTab) {
        // Nếu chưa có currentTab, xác định dựa trên trạng thái
        if (!selectedProvince) {
            currentTab = 'province';
        } else if (!selectedDistrict) {
            currentTab = 'district';
        } else {
            currentTab = 'ward';
        }
    }
    
    // Lấy thông tin ngữ cảnh (đã chọn tỉnh/quận chưa)
    const provinceCode = selectedProvince ? selectedProvince.code : null;
    const districtCode = selectedDistrict ? selectedDistrict.code : null;
    
    // Xây dựng URL với tham số ngữ cảnh
    let url = `<?= BASE_URL ?>?action=address-search&keyword=${encodeURIComponent(keyword)}`;
    if (provinceCode) {
        url += `&province_code=${encodeURIComponent(provinceCode)}`;
    }
    if (districtCode) {
        url += `&district_code=${encodeURIComponent(districtCode)}`;
    }
    
    // Hiển thị loading
    container.innerHTML = '<div class="text-center py-3"><div class="spinner-border spinner-border-sm" role="status"></div> Đang tìm kiếm...</div>';
    
    fetch(url)
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.success && data.results && data.results.length > 0) {
                // Xác định loại kết quả dựa trên tab hiện tại hoặc kết quả tìm kiếm
                let resultType = currentTab;
                if (data.results.length > 0) {
                    // Nếu tất cả kết quả cùng loại, sử dụng loại đó
                    const firstType = data.results[0].type;
                    if (data.results.every(r => r.type === firstType)) {
                        resultType = firstType;
                    }
                }
                
                // Chuyển đổi kết quả tìm kiếm sang format của danh sách địa chỉ
                const items = data.results.map(result => {
                    if (result.type === 'province') {
                        return { code: result.code, name: result.name };
                    } else if (result.type === 'district') {
                        return { code: result.code, name: result.name };
                    } else if (result.type === 'ward') {
                        return { code: result.code, name: result.name };
                    }
                    return null;
                }).filter(item => item !== null);
                
                // Hiển thị danh sách đã lọc trong container addressList
                if (items.length > 0) {
                    displayAddressList(items, resultType);
                } else {
                    container.innerHTML = '<div class="text-center py-3 text-muted">Không tìm thấy kết quả</div>';
                }
            } else {
                container.innerHTML = '<div class="text-center py-3 text-muted">Không tìm thấy kết quả</div>';
            }
        })
        .catch(error => {
            console.error('Error searching address:', error);
            console.error('URL:', url);
            container.innerHTML = '<div class="text-center py-3 text-danger">Có lỗi xảy ra khi tìm kiếm. Vui lòng thử lại.</div>';
        });
}

// Khôi phục danh sách địa chỉ đầy đủ
function restoreAddressList() {
    // Đảm bảo currentTab được xác định đúng
    if (!currentTab) {
        if (!selectedProvince) {
            currentTab = 'province';
        } else if (!selectedDistrict) {
            currentTab = 'district';
        } else {
            currentTab = 'ward';
        }
    }
    
    if (currentTab === 'province') {
        if (provinces && provinces.length > 0) {
            displayAddressList(provinces, 'province');
        } else {
            loadProvinces();
        }
    } else if (currentTab === 'district') {
        if (selectedProvince) {
            if (districts && districts.length > 0) {
                displayAddressList(districts, 'district');
            } else {
                loadDistricts(selectedProvince.code);
            }
        } else {
            // Nếu chưa chọn tỉnh, quay về tab tỉnh
            switchAddressTab('province');
            if (provinces && provinces.length > 0) {
                displayAddressList(provinces, 'province');
            } else {
                loadProvinces();
            }
        }
    } else if (currentTab === 'ward') {
        if (selectedDistrict) {
            if (wards && wards.length > 0) {
                displayAddressList(wards, 'ward');
            } else {
                loadWards(selectedDistrict.code);
            }
        } else {
            // Nếu chưa chọn quận, quay về tab quận
            if (selectedProvince) {
                switchAddressTab('district');
                if (districts && districts.length > 0) {
                    displayAddressList(districts, 'district');
                } else {
                    loadDistricts(selectedProvince.code);
                }
            } else {
                switchAddressTab('province');
                if (provinces && provinces.length > 0) {
                    displayAddressList(provinces, 'province');
                } else {
                    loadProvinces();
                }
            }
        }
    }
}

// Sắp xếp kết quả tìm kiếm theo độ liên quan
function sortSearchResults(results, keyword) {
    const keywordLower = keyword.toLowerCase();
    
    return results.sort((a, b) => {
        // Ưu tiên kết quả khớp chính xác
        const aExact = a.name.toLowerCase() === keywordLower;
        const bExact = b.name.toLowerCase() === keywordLower;
        if (aExact && !bExact) return -1;
        if (!aExact && bExact) return 1;
        
        // Ưu tiên kết quả bắt đầu bằng từ khóa
        const aStarts = a.name.toLowerCase().startsWith(keywordLower);
        const bStarts = b.name.toLowerCase().startsWith(keywordLower);
        if (aStarts && !bStarts) return -1;
        if (!aStarts && bStarts) return 1;
        
        // Ưu tiên tỉnh > quận > xã
        const typeOrder = { 'province': 1, 'district': 2, 'ward': 3 };
        const aType = typeOrder[a.type] || 99;
        const bType = typeOrder[b.type] || 99;
        if (aType !== bType) return aType - bType;
        
        // Sắp xếp theo độ dài tên (ngắn hơn = liên quan hơn)
        return a.name.length - b.name.length;
    });
}

function displaySearchResults(results, keyword = '') {
    const container = document.getElementById('addressSearchResults');
    if (!container) return;
    
    container.innerHTML = '';
    
    if (results.length === 0) {
        container.innerHTML = '<div class="address-search-item"><div class="address-search-item-name">Không tìm thấy kết quả</div></div>';
    } else {
        results.slice(0, 15).forEach(result => { // Giới hạn 15 kết quả
            const div = document.createElement('div');
            div.className = 'address-search-item';
            
            // Highlight từ khóa trong tên
            const highlightedName = highlightKeyword(result.name, keyword);
            const highlightedFull = highlightKeyword(result.full_name, keyword);
            
            // Icon theo loại
            let icon = '';
            if (result.type === 'province') {
                icon = '<i class="bi bi-geo-alt-fill text-dark"></i>';
            } else if (result.type === 'district') {
                icon = '<i class="bi bi-geo-fill text-dark"></i>';
            } else if (result.type === 'ward') {
                icon = '<i class="bi bi-geo text-secondary"></i>';
            }
            
            div.innerHTML = `
                <div class="d-flex align-items-center gap-2">
                    ${icon}
                    <div class="flex-grow-1">
                        <div class="address-search-item-name">${highlightedName}</div>
                        <div class="address-search-item-full">${highlightedFull}</div>
                    </div>
                </div>
            `;
            div.onclick = () => selectSearchResult(result);
            container.appendChild(div);
        });
        
        if (results.length > 15) {
            const moreDiv = document.createElement('div');
            moreDiv.className = 'address-search-item text-center text-muted';
            moreDiv.style.fontSize = '0.85rem';
            moreDiv.textContent = `... và ${results.length - 15} kết quả khác`;
            container.appendChild(moreDiv);
        }
    }
    
    container.classList.add('show');
}

// Highlight từ khóa trong text
function highlightKeyword(text, keyword) {
    if (!keyword || !text) return text;
    
    const regex = new RegExp(`(${keyword.replace(/[.*+?^${}()|[\\]\\\\]/g, '\\$&')})`, 'gi');
    return text.replace(regex, '<mark class="address-highlight">$1</mark>');
}

function selectSearchResult(result) {
    const searchInput = document.getElementById('addressSearchInput');
    const searchResults = document.getElementById('addressSearchResults');
    
    if (searchResults) {
        searchResults.classList.remove('show');
    }
    
    if (result.type === 'province') {
        selectedProvince = {code: result.code, name: result.name};
        document.getElementById('formCity').value = result.name;
        document.getElementById('formCityCode').value = result.code;
        // Reset district và ward
        selectedDistrict = null;
        selectedWard = null;
        document.getElementById('formDistrict').value = '';
        document.getElementById('formDistrictCode').value = '';
        document.getElementById('formWard').value = '';
        document.getElementById('formWardCode').value = '';
        
        // Xóa thanh tìm kiếm
        if (searchInput) {
            searchInput.value = '';
        }
        
        // Cập nhật địa chỉ cụ thể
        updateFormAddress();
        
        loadDistricts(result.code);
        switchAddressTab('district');
        updateSelectedAddressDisplay();
    } else if (result.type === 'district') {
        selectedProvince = {code: result.province_code, name: result.province_name};
        selectedDistrict = {code: result.code, name: result.name};
        document.getElementById('formCity').value = result.province_name;
        document.getElementById('formCityCode').value = result.province_code;
        document.getElementById('formDistrict').value = result.name;
        document.getElementById('formDistrictCode').value = result.code;
        // Reset ward
        selectedWard = null;
        document.getElementById('formWard').value = '';
        document.getElementById('formWardCode').value = '';
        
        // Xóa thanh tìm kiếm
        if (searchInput) {
            searchInput.value = '';
        }
        
        // Cập nhật địa chỉ cụ thể
        updateFormAddress();
        
        loadWards(result.code);
        switchAddressTab('ward');
        updateSelectedAddressDisplay();
    } else if (result.type === 'ward') {
        selectedProvince = {code: result.province_code, name: result.province_name};
        selectedDistrict = {code: result.district_code, name: result.district_name};
        selectedWard = {code: result.code, name: result.name};
        document.getElementById('formCity').value = result.province_name;
        document.getElementById('formCityCode').value = result.province_code;
        document.getElementById('formDistrict').value = result.district_name;
        document.getElementById('formDistrictCode').value = result.district_code;
        document.getElementById('formWard').value = result.name;
        document.getElementById('formWardCode').value = result.code;
        
        // Xóa thanh tìm kiếm
        if (searchInput) {
            searchInput.value = '';
        }
        
        // Cập nhật địa chỉ cụ thể
        updateFormAddress();
        
        updateSelectedAddressDisplay();
    }
}




// Xử lý submit form checkout qua AJAX
document.addEventListener('DOMContentLoaded', function() {
    const checkoutForm = document.getElementById('checkoutForm');
    const btnPlaceOrder = document.getElementById('btnPlaceOrder');
    
    if (checkoutForm && btnPlaceOrder) {
        checkoutForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Disable button để tránh submit nhiều lần
            btnPlaceOrder.disabled = true;
            const originalText = btnPlaceOrder.textContent;
            btnPlaceOrder.textContent = 'Đang xử lý...';
            
            // Lấy dữ liệu form
            const formData = new FormData(checkoutForm);
            
            // Gửi request qua AJAX với header để controller biết đây là AJAX request
            fetch('<?= BASE_URL ?>?action=checkout-process', {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData
            })
            .then(response => {
                // Kiểm tra content-type để biết response là JSON hay HTML
                const contentType = response.headers.get('content-type');
                if (contentType && contentType.includes('application/json')) {
                    return response.json();
                } else {
                    // Nếu không phải JSON, có thể là redirect hoặc HTML
                    // Trong trường hợp này, reload trang để xem flash message
                    window.location.reload();
                    return null;
                }
            })
            .then(data => {
                if (data) {
                    if (data.success) {
                        // Nếu là thanh toán chuyển khoản (banking), redirect đến VNPay
                        if (data.payment_method === 'banking' && data.redirect_url) {
                            window.location.href = data.redirect_url;
                            return;
                        }
                        
                        // Hiển thị modal thành công cho COD
                        showSuccessModal(data.order_id, data.total_amount, data.redirect_url || '<?= BASE_URL ?>?action=order-detail&id=' + data.order_id);
                    } else {
                        alert(data.message || 'Có lỗi xảy ra. Vui lòng thử lại.');
                        btnPlaceOrder.disabled = false;
                        btnPlaceOrder.textContent = originalText;
                    }
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Có lỗi xảy ra khi đặt hàng. Vui lòng thử lại.');
                btnPlaceOrder.disabled = false;
                btnPlaceOrder.textContent = originalText;
            });
        });
    }
});

// Hiển thị modal thành công
function showSuccessModal(orderId, totalAmount, redirectUrl) {
    const modalElement = document.getElementById('successPaymentModal');
    if (!modalElement) return;
    
    const modal = new bootstrap.Modal(modalElement);
    const orderIdElement = document.getElementById('successOrderId');
    const orderTotalElement = document.getElementById('successOrderTotal');
    const viewOrderBtn = document.getElementById('btnViewOrder');
    
    if (orderIdElement) {
        orderIdElement.textContent = '#' + orderId;
    }
    
    if (orderTotalElement && totalAmount) {
        orderTotalElement.textContent = formatCurrency(totalAmount);
    }
    
    if (viewOrderBtn) {
        viewOrderBtn.onclick = function() {
            window.location.href = redirectUrl;
        };
    }
    
    modal.show();
    
    // Tự động redirect sau 10 giây nếu user không click
    let redirectTimeout = setTimeout(function() {
        if (redirectUrl) {
            window.location.href = redirectUrl;
        }
    }, 10000);
    
    // Hủy timeout nếu user click vào nút
    if (viewOrderBtn) {
        viewOrderBtn.addEventListener('click', function() {
            clearTimeout(redirectTimeout);
        });
    }
    
    // Hủy timeout khi đóng modal
    modalElement.addEventListener('hidden.bs.modal', function() {
        clearTimeout(redirectTimeout);
    });
}

function searchAndSelectAddress(provinceName, districtName, wardName, callback) {
    if (!provinceName) {
        if (callback) callback();
        return;
    }
    
    let foundProvince = false;
    let foundDistrict = false;
    let foundWard = false;
    
    // Tìm tỉnh
    fetch(`<?= BASE_URL ?>?action=address-search&keyword=${encodeURIComponent(provinceName)}`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.results.length > 0) {
                // Tìm tỉnh khớp chính xác nhất
                let provinceResult = data.results.find(r => 
                    r.type === 'province' && 
                    r.name.toLowerCase() === provinceName.toLowerCase()
                );
                
                // Nếu không tìm thấy chính xác, tìm gần đúng
                if (!provinceResult) {
                    provinceResult = data.results.find(r => 
                        r.type === 'province' && 
                        (r.name.toLowerCase().includes(provinceName.toLowerCase()) || 
                         provinceName.toLowerCase().includes(r.name.toLowerCase()))
                    );
                }
                
                // Nếu vẫn không tìm thấy, lấy tỉnh đầu tiên
                if (!provinceResult) {
                    provinceResult = data.results.find(r => r.type === 'province');
                }
                
                if (provinceResult) {
                    foundProvince = true;
                    selectSearchResult(provinceResult);
                    
                    // Tìm quận/huyện
                    if (districtName) {
                        setTimeout(() => {
                            fetch(`<?= BASE_URL ?>?action=address-search&keyword=${encodeURIComponent(districtName)}`)
                                .then(response => response.json())
                                .then(data => {
                                    if (data.success && data.results.length > 0) {
                                        // Tìm quận/huyện khớp chính xác nhất trong tỉnh đã chọn
                                        let districtResult = data.results.find(r => 
                                            r.type === 'district' && 
                                            r.province_code === provinceResult.code &&
                                            r.name.toLowerCase() === districtName.toLowerCase()
                                        );
                                        
                                        // Nếu không tìm thấy chính xác, tìm gần đúng
                                        if (!districtResult) {
                                            districtResult = data.results.find(r => 
                                                r.type === 'district' && 
                                                r.province_code === provinceResult.code &&
                                                (r.name.toLowerCase().includes(districtName.toLowerCase()) || 
                                                 districtName.toLowerCase().includes(r.name.toLowerCase()))
                                            );
                                        }
                                        
                                        // Nếu vẫn không tìm thấy, lấy quận/huyện đầu tiên trong tỉnh
                                        if (!districtResult) {
                                            districtResult = data.results.find(r => 
                                                r.type === 'district' && 
                                                r.province_code === provinceResult.code
                                            );
                                        }
                                        
                                        if (districtResult) {
                                            foundDistrict = true;
                                            selectSearchResult(districtResult);
                                            
                                            // Tìm phường/xã
                                            if (wardName) {
                                                setTimeout(() => {
                                                    fetch(`<?= BASE_URL ?>?action=address-search&keyword=${encodeURIComponent(wardName)}`)
                                                        .then(response => response.json())
                                                        .then(data => {
                                                            if (data.success && data.results.length > 0) {
                                                                // Tìm phường/xã khớp chính xác nhất trong quận/huyện đã chọn
                                                                let wardResult = data.results.find(r => 
                                                                    r.type === 'ward' && 
                                                                    r.district_code === districtResult.code &&
                                                                    r.name.toLowerCase() === wardName.toLowerCase()
                                                                );
                                                                
                                                                // Nếu không tìm thấy chính xác, tìm gần đúng
                                                                if (!wardResult) {
                                                                    wardResult = data.results.find(r => 
                                                                        r.type === 'ward' && 
                                                                        r.district_code === districtResult.code &&
                                                                        (r.name.toLowerCase().includes(wardName.toLowerCase()) || 
                                                                         wardName.toLowerCase().includes(r.name.toLowerCase()))
                                                                    );
                                                                }
                                                                
                                                                if (wardResult) {
                                                                    foundWard = true;
                                                                    selectSearchResult(wardResult);
                                                                }
                                                            }
                                                            
                                                            // Callback sau khi tìm xong
                                                            if (callback) callback();
                                                        })
                                                        .catch(error => {
                                                            console.error('Error searching ward:', error);
                                                            if (callback) callback();
                                                        });
                                                }, 800);
                                            } else {
                                                if (callback) callback();
                                            }
                                        } else {
                                            if (callback) callback();
                                        }
                                    } else {
                                        if (callback) callback();
                                    }
                                })
                                .catch(error => {
                                    console.error('Error searching district:', error);
                                    if (callback) callback();
                                });
                        }, 800);
                    } else {
                        if (callback) callback();
                    }
                } else {
                    if (callback) callback();
                }
            } else {
                if (callback) callback();
            }
        })
        .catch(error => {
            console.error('Error searching province:', error);
            if (callback) callback();
        });
}

</script>

<div class="checkout-page">
    <div class="container checkout-container">
        <form action="<?= BASE_URL ?>?action=checkout-process" method="POST" id="checkoutForm">
            <div class="row g-4">
                <!-- Thông tin giao hàng -->
                <div class="col-lg-7">
                    <div class="checkout-card">
                        <h2 class="checkout-title">Thông Tin Giao Hàng</h2>
                        
                        <!-- Hiển thị địa chỉ mặc định -->
                        <div class="address-section">
                            <div class="address-header">
                                <i class="bi bi-geo-alt-fill"></i>
                                <h3>Địa Chỉ Nhận Hàng</h3>
                            </div>
                            
                            <?php 
                            $displayAddress = $defaultAddress;
                            $displayAddressId = null;
                            if (!empty($userAddresses)) {
                                foreach ($userAddresses as $addr) {
                                    if ($addr['is_default']) {
                                        $displayAddress = $addr;
                                        $displayAddressId = $addr['address_id'];
                                        break;
                                    }
                                }
                            }
                            ?>
                            
                            <?php if ($displayAddress): ?>
                            <div class="default-address-card">
                                <div class="default-address-info">
                                    <div class="default-address-name"><?= htmlspecialchars($displayAddress['fullname']) ?></div>
                                    <div class="default-address-phone"><?= htmlspecialchars($displayAddress['phone']) ?></div>
                                    <div class="default-address-detail">
                                        <?= htmlspecialchars($displayAddress['address']) ?>
                                        <?php if ($displayAddress['ward'] || $displayAddress['district'] || $displayAddress['city']): ?>
                                        <br><?= htmlspecialchars(trim(($displayAddress['ward'] ?? '') . ', ' . ($displayAddress['district'] ?? '') . ', ' . ($displayAddress['city'] ?? ''), ', ')) ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="address-actions">
                                    <?php if ($displayAddressId): ?>
                                    <button class="badge-default">Mặc định</button>
                                    <?php endif; ?>
                                    <?php if (isset($_SESSION['user'])): ?>
                                    <a href="#" class="btn-change-address" data-bs-toggle="modal" data-bs-target="#addressModal">
                                        Thay đổi
                                    </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php else: ?>
                            <div class="default-address-card">
                                <p class="text-muted mb-0">Chưa có địa chỉ. Vui lòng nhập địa chỉ bên dưới.</p>
                                <?php if (isset($_SESSION['user'])): ?>
                                <a href="#" class="btn-change-address mt-2" data-bs-toggle="modal" data-bs-target="#addressModal">
                                    Chọn địa chỉ đã lưu
                                </a>
                                <?php endif; ?>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Form nhập địa chỉ (ẩn nếu có địa chỉ mặc định) -->
                        <div id="addressForm" class="<?= $displayAddress ? 'd-none' : '' ?>">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Họ tên</label>
                                    <input type="text" class="form-control" name="fullname" id="fullname" required 
                                           value="<?= htmlspecialchars($defaultAddress['fullname'] ?? '') ?>" 
                                           placeholder="Nguyễn Văn A">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Số điện thoại</label>
                                    <input type="tel" class="form-control" name="phone" id="phone" required 
                                           value="<?= htmlspecialchars($defaultAddress['phone'] ?? '') ?>" 
                                           placeholder="0912345678">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Email</label>
                                    <input type="email" class="form-control" name="email" id="email" required 
                                           value="<?= htmlspecialchars($defaultAddress['email'] ?? ($user['email'] ?? '')) ?>" 
                                           placeholder="email@example.com">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Địa chỉ nhận hàng</label>
                                    <input type="text" class="form-control" name="address" id="address" required 
                                           value="<?= htmlspecialchars($defaultAddress['address'] ?? '') ?>" 
                                           placeholder="Số nhà, tên đường, phường/xã...">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Tỉnh / Thành</label>
                                    <select class="form-select form-control" name="city" id="city">
                                        <option value="">Chọn tỉnh/thành</option>
                                        <option value="Hà Nội" <?= ($defaultAddress['city'] ?? '') === 'Hà Nội' ? 'selected' : '' ?>>Hà Nội</option>
                                        <option value="TP. Hồ Chí Minh" <?= ($defaultAddress['city'] ?? '') === 'TP. Hồ Chí Minh' ? 'selected' : '' ?>>TP. Hồ Chí Minh</option>
                                        <option value="Đà Nẵng" <?= ($defaultAddress['city'] ?? '') === 'Đà Nẵng' ? 'selected' : '' ?>>Đà Nẵng</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Quận / Huyện</label>
                                    <select class="form-select form-control" name="district" id="district">
                                        <option value="">Chọn quận/huyện</option>
                                        <option value="Quận 1" <?= ($defaultAddress['district'] ?? '') === 'Quận 1' ? 'selected' : '' ?>>Quận 1</option>
                                        <option value="Quận 2" <?= ($defaultAddress['district'] ?? '') === 'Quận 2' ? 'selected' : '' ?>>Quận 2</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Phường / Xã</label>
                                    <select class="form-select form-control" name="ward" id="ward">
                                        <option value="">Chọn phường/xã</option>
                                        <option value="Phường Bến Nghé" <?= ($defaultAddress['ward'] ?? '') === 'Phường Bến Nghé' ? 'selected' : '' ?>>Phường Bến Nghé</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Ghi chú đơn hàng (Tùy chọn)</label>
                                <textarea class="form-control" name="note" rows="3" placeholder="Ví dụ: Giao hàng giờ hành chính..."></textarea>
                            </div>
                                <?php if (isset($_SESSION['user'])): ?>
                                <div class="col-12">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="save_address" id="save_address" value="1">
                                        <label class="form-check-label" for="save_address">
                                            Lưu địa chỉ này để sử dụng sau
                                        </label>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Tóm tắt đơn hàng -->
                <div class="col-lg-5">
                    <div class="checkout-card">
                        <h2 class="checkout-title">Đơn Hàng Của Bạn</h2>
                        
                        <div class="order-items mb-4">
                            <?php foreach ($cart as $item): ?>
                                <?php 
                                // Xử lý ảnh sử dụng helper function
                                $imgSrc = getProductImageUrl($item['image'] ?? '', true);
                                ?>
                                <div class="order-summary-item">
                                    <img src="<?= $imgSrc ?>" alt="<?= $item['name'] ?>" class="order-img" onerror="this.src='<?= BASE_URL ?>assets/images/logo.png'">
                                    <div class="order-info">
                                        <span class="order-name"><?= $item['name'] ?></span>
                                        <div class="order-meta">
                                            Size: <?= $item['size'] ?? 'M' ?> | Màu: <?= $item['color'] ?? 'Black' ?> <br>
                                            SL: <?= $item['quantity'] ?>
                                        </div>
                                    </div>
                                    <div class="order-price">
                                        <?= number_format($item['price'] * $item['quantity'], 0, ',', '.') ?> đ
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <!-- Thông tin mã giảm giá đã áp dụng -->
                        <?php 
                        $appliedCoupon = $_SESSION['applied_coupon'] ?? null;
                        $discountAmount = 0;
                        $finalTotal = $total;
                        
                        if ($appliedCoupon) {
                            $discountAmount = $appliedCoupon['discount_amount'] ?? 0;
                            $finalTotal = $total - $discountAmount;
                        }
                        ?>
                        
                        <?php if ($appliedCoupon): ?>
                        <div class="coupon-section checkout-applied-coupon mb-4">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <label class="form-label checkout-applied-coupon-label mb-1">
                                        <i class="bi bi-check-circle-fill text-dark"></i> Mã giảm giá đã áp dụng
                                    </label>
                                    <div>
                                        <strong class="checkout-applied-coupon-code"><?= htmlspecialchars($appliedCoupon['code']) ?></strong>
                                        <?php if (!empty($appliedCoupon['name'])): ?>
                                            <small class="d-block text-muted"><?= htmlspecialchars($appliedCoupon['name']) ?></small>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="text-end">
                                    <div class="checkout-applied-coupon-amount">
                                        -<?= number_format($discountAmount, 0, ',', '.') ?> đ
                                    </div>
                                </div>
                            </div>
                            <input type="hidden" name="coupon_id" value="<?= $appliedCoupon['id'] ?? '' ?>">
                            <input type="hidden" name="applied_coupon_code" value="<?= htmlspecialchars($appliedCoupon['code']) ?>">
                            <input type="hidden" name="discount_amount" value="<?= $discountAmount ?>">
                        </div>
                        <?php else: ?>
                            <input type="hidden" name="coupon_id" value="">
                            <input type="hidden" name="applied_coupon_code" value="">
                            <input type="hidden" name="discount_amount" value="0">
                        <?php endif; ?>
                        
                        <div class="checkout-summary">
                            <div class="checkout-total-row">
                                <span>Tạm tính</span>
                                <span id="subtotalAmount"><?= number_format($total, 0, ',', '.') ?> đ</span>
                            </div>
                            <?php if ($appliedCoupon && $discountAmount > 0): ?>
                            <div class="checkout-total-row">
                                <span>Giảm giá</span>
                                <span id="discountAmountDisplay" class="checkout-discount-amount">
                                    -<?= number_format($discountAmount, 0, ',', '.') ?> đ
                                </span>
                            </div>
                            <?php endif; ?>
                            <div class="checkout-total-row">
                                <span>Phí vận chuyển</span>
                                <span>Miễn phí</span>
                            </div>
                            <div class="checkout-total-row checkout-final-total">
                                <span>Tổng cộng</span>
                                <span id="finalTotalAmount"><?= number_format($finalTotal, 0, ',', '.') ?> đ</span>
                            </div>
                        </div>
                        
                        <div class="mt-4">
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="radio" name="payment_method" id="cod" value="cod" checked>
                                <label class="form-check-label fw-semibold" for="cod">
                                    Thanh toán khi nhận hàng (COD)
                                </label>
                            </div>
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="radio" name="payment_method" id="banking" value="banking">
                                <label class="form-check-label fw-semibold" for="banking">
                                    Chuyển khoản ngân hàng
                                </label>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn-place-order" id="btnPlaceOrder">ĐẶT HÀNG</button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Modal Thanh Toán Thành Công -->
<div class="modal fade" id="successPaymentModal" tabindex="-1" aria-labelledby="successPaymentModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-body text-center py-5">
                <div class="success-icon mb-4">
                    <i class="bi bi-check-circle-fill text-success" style="font-size: 80px;"></i>
                </div>
                <h3 class="modal-title mb-3" id="successPaymentModalLabel">Đặt Hàng Thành Công!</h3>
                <p class="text-muted mb-4">Cảm ơn bạn đã đặt hàng. Chúng tôi sẽ liên hệ với bạn sớm nhất có thể.</p>
                <div class="order-info mb-4">
                    <p class="mb-2"><strong>Mã đơn hàng:</strong> <span id="successOrderId" class="text-primary"></span></p>
                    <p class="mb-0"><strong>Tổng tiền:</strong> <span id="successOrderTotal" class="text-primary"></span></p>
                </div>
                <div class="d-flex gap-3 justify-content-center">
                    <button type="button" class="btn btn-outline-secondary" onclick="window.location.href='<?= BASE_URL ?>'">
                        Về Trang Chủ
                    </button>
                    <button type="button" class="btn btn-dark" id="btnViewOrder">
                        Xem Đơn Hàng
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Chọn Địa Chỉ -->
<?php if (isset($_SESSION['user'])): ?>
<div class="modal fade address-modal" id="addressModal" tabindex="-1" aria-labelledby="addressModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addressModalLabel">Địa Chỉ Của Tôi</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <?php if (!empty($userAddresses)): ?>
                    <?php foreach ($userAddresses as $addr): ?>
                    <div class="address-item-modal d-flex align-items-start <?= $addr['is_default'] ? 'selected' : '' ?>" 
                         onclick="selectAddressFromModal(<?= $addr['address_id'] ?>, this)">
                        <div class="address-radio">
                            <input type="radio" name="selectedAddress" value="<?= $addr['address_id'] ?>" 
                                   <?= $addr['is_default'] ? 'checked' : '' ?>
                                   onclick="event.stopPropagation(); selectAddressFromModal(<?= $addr['address_id'] ?>, this.closest('.address-item-modal'))">
                        </div>
                        <div class="address-content flex-grow-1">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <div class="address-content-name"><?= htmlspecialchars($addr['fullname']) ?></div>
                                    <div class="address-content-phone"><?= htmlspecialchars($addr['phone']) ?></div>
                                    <div class="address-content-detail">
                                        <?= htmlspecialchars($addr['address']) ?><br>
                                        <?php if ($addr['ward'] || $addr['district'] || $addr['city']): ?>
                                        <?= htmlspecialchars(trim(($addr['ward'] ?? '') . ', ' . ($addr['district'] ?? '') . ', ' . ($addr['city'] ?? ''), ', ')) ?>
                                        <?php endif; ?>
                                    </div>
                                    <?php if ($addr['is_default']): ?>
                                    <span class="badge-default badge-inline">Mặc định</span>
                                    <?php endif; ?>
                                </div>
                                <a href="#" class="address-update-link" onclick="event.stopPropagation(); openAddressFormModal(<?= $addr['address_id'] ?>); return false;">
                                    Cập nhật
                                </a>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-muted text-center py-4">Chưa có địa chỉ đã lưu</p>
                <?php endif; ?>
                
                <button type="button" class="btn-add-address" onclick="openAddressFormModal()">
                    <i class="bi bi-plus-circle"></i> Thêm Địa Chỉ Mới
                </button>
            </div>
            <div class="modal-footer-custom">
                <button type="button" class="btn-cancel" data-bs-dismiss="modal">Huỷ</button>
                <button type="button" class="btn-confirm" onclick="confirmAddressSelection()">Xác nhận</button>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Modal Thêm/Sửa Địa Chỉ -->
<div class="modal fade address-modal" id="addressFormModal" tabindex="-1" aria-labelledby="addressFormModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addressFormModalLabel">Địa chỉ mới</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="addressFormData" onsubmit="saveAddress(event)">
                    <input type="hidden" id="formAddressId" name="address_id" value="">
                    
                    <div class="address-form-group">
                        <label class="address-form-label">Họ và tên</label>
                        <input type="text" class="form-control" id="formFullname" name="fullname" required>
                    </div>
                    
                    <div class="address-form-group">
                        <label class="address-form-label">Số điện thoại</label>
                        <input type="tel" class="form-control" id="formPhone" name="phone" required>
                    </div>
                    
                    <div class="address-form-group">
                        <label class="address-form-label">Email</label>
                        <input type="email" class="form-control" id="formEmail" name="email" required>
                    </div>
                    
                    <div class="address-form-group">
                        <label class="address-form-label">Tỉnh/ Thành phố, Quận/Huyện, Phường/Xã</label>
                        
                        <!-- Tìm kiếm địa chỉ -->
                        <div class="address-search-wrapper mb-3">
                            <div class="position-relative">
                                <input type="text" 
                                       class="form-control address-search-input" 
                                       id="addressSearchInput" 
                                       placeholder="Tỉnh/ Thành phố, Quận/Huyện, Phường/Xã"
                                       autocomplete="off">
                                <i class="bi bi-search address-search-icon"></i>
                                <div class="address-search-results" id="addressSearchResults"></div>
                            </div>
                        </div>
                        
                        <!-- Tabs chọn địa chỉ -->
                        <div class="address-tabs mb-3">
                            <button type="button" class="address-tab active" data-tab="province" onclick="switchAddressTab('province')">
                                Tỉnh/Thành phố
                            </button>
                            <button type="button" class="address-tab" data-tab="district" onclick="switchAddressTab('district')" disabled>
                                Quận/Huyện
                            </button>
                            <button type="button" class="address-tab" data-tab="ward" onclick="switchAddressTab('ward')" disabled>
                                Phường/ Xã
                            </button>
                        </div>
                        
                        <!-- Danh sách địa chỉ -->
                        <div class="address-list-container" id="addressListContainer">
                            <div class="address-list" id="addressList">
                                <!-- Sẽ được load động -->
                            </div>
                        </div>
                        
                        <!-- Hidden inputs để lưu giá trị -->
                        <input type="hidden" id="formCity" name="city" value="">
                        <input type="hidden" id="formCityCode" name="city_code" value="">
                        <input type="hidden" id="formDistrict" name="district" value="">
                        <input type="hidden" id="formDistrictCode" name="district_code" value="">
                        <input type="hidden" id="formWard" name="ward" value="">
                        <input type="hidden" id="formWardCode" name="ward_code" value="">
                        
                        <!-- Hiển thị địa chỉ đã chọn -->
                        <div class="selected-address-display mt-3 d-none" id="selectedAddressDisplay">
                            <div class="selected-address-text" id="selectedAddressText"></div>
                            <button type="button" class="btn-clear-address" onclick="clearSelectedAddress()">
                                <i class="bi bi-x"></i>
                            </button>
                        </div>
                    </div>
                    
                    <div class="address-form-group">
                        <label class="address-form-label">Địa chỉ cụ thể</label>
                        <input type="text" class="form-control" id="formAddress" name="address" required placeholder="Số nhà, tên đường...">
                    </div>
                    
                    <div class="address-type-buttons">
                        <button type="button" class="address-type-btn active" onclick="selectAddressType('home')">
                            Nhà Riêng
                        </button>
                        <button type="button" class="address-type-btn" onclick="selectAddressType('office')">
                            Văn Phòng
                        </button>
                        <input type="hidden" id="addressType" name="address_type" value="home">
                    </div>
                    
                    <div class="form-check-default">
                        <input type="checkbox" id="setAsDefault" name="is_default" value="1">
                        <label for="setAsDefault" class="set-default-label">Đặt làm địa chỉ mặc định</label>
                    </div>
                </form>
            </div>
            <div class="modal-footer-custom">
                <button type="button" class="btn-back" onclick="document.getElementById('addressFormModal').querySelector('.btn-close').click()">Trở Lại</button>
                <button type="button" class="btn-complete" onclick="document.getElementById('addressFormData').requestSubmit()">Hoàn thành</button>
            </div>
        </div>
    </div>
</div>


<script>
// Address Management Variables - PHẢI khai báo ở đầu script để tránh lỗi "Cannot access before initialization"
let currentTab = 'province';
let selectedProvince = null;
let selectedDistrict = null;
let selectedWard = null;
let provinces = [];
let districts = [];
let wards = [];
let searchTimeout; // Khai báo searchTimeout ở đây để tránh lỗi "Cannot access before initialization"

// Lưu địa chỉ
function saveAddress(event) {
    event.preventDefault();
    
    // Validation
    const fullname = document.getElementById('formFullname').value.trim();
    const phone = document.getElementById('formPhone').value.trim();
    const email = document.getElementById('formEmail').value.trim();
    const address = document.getElementById('formAddress').value.trim();
    const city = document.getElementById('formCity').value.trim();
    
    // Validate họ tên
    if (!fullname) {
        alert('Vui lòng nhập họ và tên');
        document.getElementById('formFullname').focus();
        return;
    }
    if (fullname.length < 2) {
        alert('Họ và tên phải có ít nhất 2 ký tự');
        document.getElementById('formFullname').focus();
        return;
    }
    
    // Validate số điện thoại
    if (!phone) {
        alert('Vui lòng nhập số điện thoại');
        document.getElementById('formPhone').focus();
        return;
    }
    const phoneRegex = /^(0|\+84)[0-9]{9,10}$/;
    if (!phoneRegex.test(phone.replace(/\s/g, ''))) {
        alert('Số điện thoại không hợp lệ. Vui lòng nhập số điện thoại 10-11 số');
        document.getElementById('formPhone').focus();
        return;
    }
    
    // Validate email
    if (!email) {
        alert('Vui lòng nhập email');
        document.getElementById('formEmail').focus();
        return;
    }
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(email)) {
        alert('Email không hợp lệ. Vui lòng nhập đúng định dạng email');
        document.getElementById('formEmail').focus();
        return;
    }
    
    // Validate địa chỉ cụ thể
    if (!address) {
        alert('Vui lòng nhập địa chỉ cụ thể (số nhà, tên đường)');
        document.getElementById('formAddress').focus();
        return;
    }
    if (address.length < 5) {
        alert('Địa chỉ cụ thể phải có ít nhất 5 ký tự');
        document.getElementById('formAddress').focus();
        return;
    }
    
    // Validate tỉnh/thành phố
    if (!city) {
        alert('Vui lòng chọn tỉnh/thành phố');
        document.getElementById('addressSearchInput').focus();
        return;
    }
    
    const addressId = document.getElementById('formAddressId').value;
    const data = {
        address_id: addressId || null,
        fullname: fullname,
        phone: phone,
        email: email,
        address: address,
        city: city,
        district: document.getElementById('formDistrict').value.trim(),
        ward: document.getElementById('formWard').value.trim(),
        address_type: document.getElementById('addressType').value,
        is_default: document.getElementById('setAsDefault').checked ? 1 : 0
    };
    
    // Disable submit button
    const submitBtn = document.querySelector('#addressFormModal .btn-complete');
    const originalText = submitBtn.textContent;
    submitBtn.disabled = true;
    submitBtn.textContent = 'Đang lưu...';
    
    fetch('<?= BASE_URL ?>?action=save-address', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            // Đóng modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('addressFormModal'));
            modal.hide();
            
            // Reload để cập nhật danh sách địa chỉ
            setTimeout(() => {
                location.reload();
            }, 300);
        } else {
            alert('Có lỗi xảy ra: ' + (result.message || 'Vui lòng thử lại'));
            submitBtn.disabled = false;
            submitBtn.textContent = originalText;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Có lỗi xảy ra. Vui lòng thử lại.');
        submitBtn.disabled = false;
        submitBtn.textContent = originalText;
    });
}
</script>
