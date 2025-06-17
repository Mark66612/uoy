document.addEventListener('DOMContentLoaded', function() {
    // опять же, надо из бд брать, но что имеем
    const generateApplications = () => {
        const courses = [
            "Веб-разработка для начинающих", 
            "Продвинутый JavaScript", 
            "Дизайн интерфейсов", 
            "Аналитика данных", 
            "Мобильная разработка"
        ];
        
        const statuses = ["Новая", "Идет обучение", "Обучение завершено"];
        const paymentMethods = ["Картой", "Наличные", "Рассрочка", "Перевод"];
        
        const applications = [];
        
        for (let i = 1; i <= 5; i++) {
            const randomCourse = courses[Math.floor(Math.random() * courses.length)];
            const randomStatus = statuses[Math.floor(Math.random() * statuses.length)];
            const randomPayment = paymentMethods[Math.floor(Math.random() * paymentMethods.length)];
            
            const startDate = new Date();
            startDate.setDate(startDate.getDate() + Math.floor(Math.random() * 30));
            
            const createDate = new Date();
            createDate.setDate(createDate.getDate() - Math.floor(Math.random() * 60));
            
            applications.push({
                id: i,
                course_name: randomCourse,
                start_date: startDate.toISOString().split('T')[0],
                payment_method: randomPayment,
                status: randomStatus,
                created_at: createDate.toISOString()
            });
        }
        
        return applications;
    };
    
    const generateReviews = () => {
        const courses = [
            "Веб-разработка для начинающих", 
            "Продвинутый JavaScript", 
            "Дизайн интерфейсов"
        ];
        
        const reviews = [];
        
        const reviewTexts = [
            "Отличный курс! Материал изложен доступно, преподаватели всегда готовы помочь. Узнал много нового и полезного для своей работы.",
            "Курс полностью оправдал мои ожидания. Практические задания помогли закрепить теорию. Рекомендую всем, кто хочет освоить новую профессию.",
            "Хороший курс для старта в новой области. Некоторые темы можно было бы раскрыть глубже, но в целом я доволен обучением."
        ];
        
        for (let i = 0; i < 3; i++) {
            const createDate = new Date();
            createDate.setDate(createDate.getDate() - Math.floor(Math.random() * 30));
            
            reviews.push({
                id: i + 1,
                course_name: courses[i],
                content: reviewTexts[i],
                created_at: createDate.toISOString()
            });
        }
        
        return reviews;
    };
    
    const applications = generateApplications();
    const reviews = generateReviews();
    
    // Заявки пользователя
    const renderApplications = () => {
        const applicationsList = document.getElementById('applications-list');
        
        if (applications.length === 0) {
            applicationsList.innerHTML = `
                <div style="text-align: center; padding: 30px 0;">
                    <i class="fas fa-inbox" style="font-size: 48px; color: #ddd; margin-bottom: 15px;"></i>
                    <h3>У вас пока нет заявок</h3>
                    <p>Создайте свою первую заявку на обучение</p>
                </div>
            `;
            return;
        }
        
        applicationsList.innerHTML = '';
        
        applications.forEach(app => {
            const formattedDate = new Date(app.created_at).toLocaleDateString('ru-RU');
            const startDate = new Date(app.start_date).toLocaleDateString('ru-RU');
            
            const appElement = document.createElement('div');
            appElement.className = 'application-item';
            appElement.innerHTML = `
                <div class="application-header">
                    <div class="application-title">${app.course_name}</div>
                    <div class="application-date">${formattedDate}</div>
                </div>
                
                <div class="application-meta">
                    <div class="application-meta-item">
                        <i class="fas fa-calendar-alt"></i>
                        Начало: ${startDate}
                    </div>
                    
                    <div class="application-meta-item">
                        <i class="fas fa-money-bill-wave"></i>
                        Оплата: ${app.payment_method}
                    </div>
                </div>
                
                <div class="application-meta">
                    <div class="application-meta-item">
                        <i class="fas fa-info-circle"></i>
                        Статус: 
                        <span class="application-status status-${app.status.replace(' ', '_')}">
                            ${app.status}
                        </span>
                    </div>
                </div>
                
                ${app.status === 'Обучение завершено' ? `
                    <button class="btn btn-success toggle-review" style="margin-top: 10px;">
                        <i class="fas fa-comment"></i> Оставить отзыв
                    </button>
                    
                    <div class="review-form">
                        <form class="review-form-element">
                            <input type="hidden" name="application_id" value="${app.id}">
                            
                            <div class="form-group">
                                <label>Ваш отзыв о курсе</label>
                                <textarea name="content" rows="3" placeholder="Расскажите о вашем опыте обучения..." required></textarea>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-paper-plane"></i> Отправить отзыв
                            </button>
                        </form>
                    </div>
                ` : ''}
            `;
            
            applicationsList.appendChild(appElement);
        });
        
        // обрабатываем кнопки отзывов
        document.querySelectorAll('.toggle-review').forEach(button => {
            button.addEventListener('click', function() {
                const form = this.nextElementSibling;
                form.style.display = form.style.display === 'block' ? 'none' : 'block';
            });
        });
        
        // обрабатываем формы отзывов
        document.querySelectorAll('.review-form-element').forEach(form => {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const formData = new FormData(this);
                const content = formData.get('content');
                
                if (content.trim() === '') {
                    alert('Пожалуйста, напишите ваш отзыв');
                    return;
                }
                
                // новый отзыв
                const newReview = {
                    id: reviews.length + 1,
                    course_name: this.closest('.application-item').querySelector('.application-title').textContent,
                    content: content,
                    created_at: new Date().toISOString()
                };
                
                reviews.unshift(newReview);
                renderReviews();
                
                this.closest('.review-form').style.display = 'none';
                
                showSuccessMessage('Отзыв успешно отправлен!');
            });
        });
    };
    
    // отзывы
    const renderReviews = () => {
        const reviewsList = document.getElementById('reviews-list');
        
        if (reviews.length === 0) {
            reviewsList.innerHTML = `
                <div style="text-align: center; padding: 20px 0; color: #95a5a6;">
                    <i class="fas fa-comment-slash" style="font-size: 36px; margin-bottom: 10px;"></i>
                    <p>Вы еще не оставляли отзывов</p>
                </div>
            `;
            return;
        }
        
        reviewsList.innerHTML = '';
        
        reviews.forEach(review => {
            const formattedDate = new Date(review.created_at).toLocaleDateString('ru-RU', {
                day: '2-digit',
                month: '2-digit',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
            
            const reviewElement = document.createElement('div');
            reviewElement.className = 'review-item';
            reviewElement.innerHTML = `
                <div class="review-header">
                    <div class="review-author">${review.course_name}</div>
                    <div class="review-date">${formattedDate}</div>
                </div>
                
                <div class="review-content">${review.content}</div>
            `;
            
            reviewsList.appendChild(reviewElement);
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
    
    // редактирование профиля
    const initProfileEditing = () => {
        const editBtn = document.getElementById('edit-profile-btn');
        const profileInfo = document.querySelector('.user-profile');
        
        editBtn.addEventListener('click', function() {
            if (profileInfo.querySelector('.profile-form')) {
                profileInfo.querySelector('.profile-form').remove();
                return;
            }
            
            const profileForm = document.createElement('div');
            profileForm.className = 'profile-form';
            profileForm.innerHTML = `
                <div class="form-group">
                    <label>ФИО</label>
                    <input type="text" value="Половинкина Елена Олеговна">
                </div>
                
                <div class="form-group">
                    <label>Телефон</label>
                    <input type="text" value="+7(902)-123-45-67">
                </div>
                
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" value="polov@example.com">
                </div>
                
                <button class="btn btn-primary">Сохранить изменения</button>
                <button class="btn btn-outline cancel-edit">Отмена</button>
            `;
            
            profileInfo.appendChild(profileForm);
            
            // отмена
            profileForm.querySelector('.cancel-edit').addEventListener('click', function() {
                profileForm.remove();
            });
            
            // сохранить
            profileForm.querySelector('.btn-primary').addEventListener('click', function() {
                showSuccessMessage('Данные профиля успешно обновлены!');
                profileForm.remove();
            });
        });
    };
    
    const initPage = () => {
        renderApplications();
        renderReviews();
        initProfileEditing();
    };
    
    initPage();
});