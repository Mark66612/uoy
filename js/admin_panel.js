document.addEventListener('DOMContentLoaded', function() {
    // данные которые должны браться из бд, но ничего не работает, поэтому так
    const generateApplications = () => {
        const courses = [
            "Веб-разработка для начинающих", 
            "Продвинутый JavaScript", 
            "Дизайн интерфейсов", 
            "Аналитика данных", 
            "Мобильная разработка"
        ];
        
        const names = [
            "Иванов Иван Иванович", 
            "Петров Петр Петрович", 
            "Сидорова Анна Сергеевна", 
            "Кузнецов Дмитрий Алексеевич", 
            "Смирнова Екатерина Владимировна"
        ];
        
        const statuses = ["Новая", "Идет обучение", "Обучение завершено"];
        const paymentMethods = ["Картой", "Наличные", "Рассрочка", "Перевод"];
        
        const applications = [];
        
        for (let i = 1; i <= 15; i++) {
            const randomCourse = courses[Math.floor(Math.random() * courses.length)];
            const randomName = names[Math.floor(Math.random() * names.length)];
            const randomStatus = statuses[Math.floor(Math.random() * statuses.length)];
            const randomPayment = paymentMethods[Math.floor(Math.random() * paymentMethods.length)];
            
            const startDate = new Date();
            startDate.setDate(startDate.getDate() + Math.floor(Math.random() * 30));
            
            const createDate = new Date();
            createDate.setDate(createDate.getDate() - Math.floor(Math.random() * 60));
            
            applications.push({
                id: i,
                user_fullname: randomName,
                user_email: `user${i}@example.com`,
                user_phone: `+7(9${Math.floor(Math.random() * 100).toString().padStart(2, '0')})-${Math.floor(Math.random() * 1000).toString().padStart(3, '0')}-${Math.floor(Math.random() * 100).toString().padStart(2, '0')}-${Math.floor(Math.random() * 100).toString().padStart(2, '0')}`,
                course_name: randomCourse,
                start_date: startDate.toISOString().split('T')[0],
                payment_method: randomPayment,
                status: randomStatus,
                created_at: createDate.toISOString()
            });
        }
        
        return applications;
    };
    
    const applications = generateApplications();
    
    // оставленные заявки
    const renderApplications = (apps) => {
        const tableBody = document.getElementById('applications-table');
        tableBody.innerHTML = '';
        
        if (apps.length === 0) {
            tableBody.innerHTML = `
                <tr>
                    <td colspan="8" style="text-align: center; padding: 30px;">
                        <i class="fas fa-inbox" style="font-size: 48px; color: #ddd; margin-bottom: 15px;"></i>
                        <h3>Нет заявок</h3>
                        <p>Попробуйте изменить параметры фильтра</p>
                    </td>
                </tr>
            `;
            return;
        }
        
        apps.forEach(app => {
            const createdDate = new Date(app.created_at);
            const formattedDate = createdDate.toLocaleDateString('ru-RU', {
                day: '2-digit',
                month: '2-digit',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
            
            const row = document.createElement('tr');
            row.dataset.id = app.id;
            row.innerHTML = `
                <td>#${app.id}</td>
                <td>
                    <div><strong>${app.user_fullname}</strong></div>
                    <div><small>${app.user_email}</small></div>
                    <div><small>${app.user_phone}</small></div>
                </td>
                <td>${app.course_name}</td>
                <td>${app.start_date.split('-').reverse().join('.')}</td>
                <td>${app.payment_method}</td>
                <td>
                    <span class="status-badge status-${app.status.replace(' ', '_')}">
                        ${app.status}
                    </span>
                </td>
                <td>${formattedDate}</td>
                <td>
                    <form class="status-form" style="display: flex; gap: 5px;">
                        <input type="hidden" name="id" value="${app.id}">
                        <select name="status" class="status-select" style="padding: 5px; border-radius: 4px; border: 1px solid #ddd;">
                            <option value="Новая" ${app.status === 'Новая' ? 'selected' : ''}>Новая</option>
                            <option value="Идет обучение" ${app.status === 'Идет обучение' ? 'selected' : ''}>Идет обучение</option>
                            <option value="Обучение завершено" ${app.status === 'Обучение завершено' ? 'selected' : ''}>Обучение завершено</option>
                        </select>
                        <button type="submit" class="action-btn" data-tooltip="Обновить статус">
                            <i class="fas fa-sync-alt"></i>
                        </button>
                    </form>
                </td>
            `;
            
            tableBody.appendChild(row);
        });
        
        // для изменения статуса 
        document.querySelectorAll('.status-form').forEach(form => {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const formData = new FormData(this);
                const id = formData.get('id');
                const status = formData.get('status');
                
                // изменение статуса
                const app = applications.find(a => a.id == id);
                if (app) {
                    app.status = status;
                    
                    // отображение (измененного) статуса
                    const badge = document.querySelector(`tr[data-id="${id}"] .status-badge`);
                    if (badge) {
                        badge.className = `status-badge status-${status.replace(' ', '_')}`;
                        badge.textContent = status;
                    }
                    
                    showSuccessMessage(`Статус заявки #${id} успешно обновлен на "${status}"`);
                }
            });
        });
    };
    
    const showSuccessMessage = (message) => {
        document.querySelectorAll('.alert').forEach(el => el.remove());
        
        const alert = document.createElement('div');
        alert.className = 'alert alert-success';
        alert.innerHTML = `<i class="fas fa-check-circle"></i> ${message}`;
        
        const header = document.querySelector('.header');
        header.after(alert);
        
        setTimeout(() => {
            alert.remove();
        }, 5000);
    };
    
    const initFilters = () => {
        const filterForm = document.getElementById('filter-form');
        
        filterForm.addEventListener('submit', function(e) {
            e.preventDefault();
            applyFilters();
        });
        
        filterForm.addEventListener('reset', function() {
            setTimeout(() => applyFilters(), 100);
        });
    };
    
    const applyFilters = () => {
        const statusFilter = document.getElementById('filter').value;
        const searchQuery = document.getElementById('search').value.toLowerCase();
        const dateFrom = document.getElementById('date_from').value;
        const dateTo = document.getElementById('date_to').value;
        
        let filtered = [...applications];
        
        if (statusFilter) {
            filtered = filtered.filter(app => app.status === statusFilter);
        }
        
        if (searchQuery) {
            filtered = filtered.filter(app => 
                app.user_fullname.toLowerCase().includes(searchQuery) || 
                app.course_name.toLowerCase().includes(searchQuery)
            );
        }
        if (dateFrom) {
            filtered = filtered.filter(app => new Date(app.created_at) >= new Date(dateFrom));
        }
        
        if (dateTo) {
            filtered = filtered.filter(app => new Date(app.created_at) <= new Date(dateTo));
        }
        
        renderApplications(filtered);
    };
    
    const initDateFilters = () => {
        const today = new Date().toISOString().split('T')[0];
        const lastMonth = new Date();
        lastMonth.setMonth(lastMonth.getMonth() - 1);
        const lastMonthStr = lastMonth.toISOString().split('T')[0];
        
        document.getElementById('date_from').value = lastMonthStr;
        document.getElementById('date_to').value = today;
    };
    
    const initPage = () => {
        renderApplications(applications);
        initFilters();
        initDateFilters();
    };
    
    initPage();
});