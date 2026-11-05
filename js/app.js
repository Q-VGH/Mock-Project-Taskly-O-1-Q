// =============================================
// FILE: app.js
// MÔ TẢ: Toàn bộ logic JavaScript cho ứng dụng Todo List
// TÁC GIẢ: Dev 2
// =============================================

// ===== PHẦN 1: KHAI BÁO BIẾN =====
// Lấy các phần tử HTML bằng id để JS có thể thao tác

// Đường dẫn đến thư mục API (Backend PHP)
// Khi chạy trên Laragon: http://localhost/todo-list/api/
const API_URL = "api";

// Lấy phần tử form (bao gồm ô input và nút thêm)
const todoForm = document.getElementById("todo-form");

// Lấy ô input nhập task
const todoInput = document.getElementById("todo-input");

// Lấy danh sách <ul> để thêm các <li> vào
const todoList = document.getElementById("todo-list");

// Lấy phần tử hiển thị lỗi
const errorMessage = document.getElementById("error-message");

// Lấy phần tử hiển thị khi danh sách rỗng
const emptyMessage = document.getElementById("empty-message");

// Lấy các phần tử thống kê ở footer
const totalCount = document.getElementById("total-count");
const completedCount = document.getElementById("completed-count");
const pendingCount = document.getElementById("pending-count");

// ===== PHẦN 2: HÀM HIỂN THỊ =====

/**
 * Tạo 1 phần tử <li> cho 1 task
 * @param {Object} task - Object chứa thông tin task {id, title, status}
 * @returns {HTMLElement} - Phần tử <li> đã tạo
 */
function createTaskElement(task) {
  // Tạo thẻ <li>
  const li = document.createElement("li");
  li.className = "todo-item";
  // Lưu id của task vào thuộc tính data-id (để dùng khi sửa/xóa)
  li.dataset.id = task.id;

  // Nếu task đã hoàn thành → thêm class 'completed' (CSS sẽ gạch ngang)
  if (task.status === "completed") {
    li.classList.add("completed");
  }

  // Tạo nội dung HTML bên trong <li>
  li.innerHTML = `
        <!-- Checkbox: đánh dấu hoàn thành -->
        <input 
            type="checkbox" 
            class="todo-checkbox" 
            ${task.status === "completed" ? "checked" : ""}
            onchange="toggleTask(${task.id}, this.checked)"
        >
        
        <!-- Nội dung task -->
        <span class="todo-text">${escapeHtml(task.title)}</span>
        
        <!-- Nhóm nút hành động -->
        <div class="todo-actions">
            <button class="btn btn-edit" onclick="startEdit(${task.id})">✏️ Sửa</button>
            <button class="btn btn-delete" onclick="deleteTask(${task.id})">🗑️ Xóa</button>
        </div>
    `;
  // Giải thích:
  // - ${...} là Template Literal: chèn biến JS vào chuỗi
  // - onchange="toggleTask(...)" : Khi checkbox thay đổi → gọi hàm toggleTask
  // - onclick="startEdit(...)" : Khi bấm nút Sửa → gọi hàm startEdit
  // - onclick="deleteTask(...)" : Khi bấm nút Xóa → gọi hàm deleteTask
  // - escapeHtml() : Chống XSS (giải thích ở phần 4)

  return li;
}

/**
 * Render toàn bộ danh sách task lên giao diện
 * @param {Array} tasks - Mảng các task [{id, title, status}, ...]
 */
function renderTasks(tasks) {
  // Xóa hết nội dung cũ trong <ul>
  todoList.innerHTML = "";

  // Nếu không có task nào → hiện thông báo rỗng
  if (tasks.length === 0) {
    emptyMessage.style.display = "block";
  } else {
    emptyMessage.style.display = "none";

    // Lặp qua từng task và tạo <li>, sau đó thêm vào <ul>
    tasks.forEach(function (task) {
      const li = createTaskElement(task);
      todoList.appendChild(li);
    });
  }

  // Cập nhật thống kê ở footer
  updateStats(tasks);
}

// ===== PHẦN 3: HÀM GỌI API (Giao tiếp với Backend PHP) =====

/**
 * Đọc danh sách task từ Backend (READ)
 * Gọi khi trang load lần đầu
 */
async function loadTasks() {
  try {
    // Gửi GET request đến read.php
    // fetch() trả về một Promise → dùng await để chờ kết quả
    const response = await fetch(`${API_URL}/read.php`);

    // Chuyển response từ text thành JSON object
    const data = await response.json();

    // Kiểm tra kết quả
    if (data.success) {
      // Nếu thành công → render danh sách task
      renderTasks(data.tasks);
    } else {
      showError("Không thể tải danh sách task.");
    }
  } catch (error) {
    // Nếu có lỗi (VD: server chưa chạy) → hiện thông báo
    console.error("Lỗi khi tải tasks:", error);
    showError("Lỗi kết nối server. Kiểm tra Laragon đã bật chưa?");
  }
}
// ===== TẠM THỜI: Dùng dữ liệu giả để test giao diện =====
// Khi Backend chưa xong, thay loadTasks() bằng phiên bản mock này:

// async function loadTasks() {
//   // Dữ liệu giả (mock data) - XÓA SAU KHI CÓ BACKEND
//   const mockTasks = [
//     { id: 1, title: "Học HTML cơ bản", status: "completed" },
//     { id: 2, title: "Học CSS Flexbox", status: "completed" },
//     { id: 3, title: "Học JavaScript DOM", status: "pending" },
//     { id: 4, title: "Viết API bằng PHP", status: "pending" },
//   ];

//   renderTasks(mockTasks);
// }

// Sau khi Backend xong → XÓA hàm mock này → BỎ COMMENT hàm loadTasks() thật
/**
 * Thêm task mới (CREATE)
 * Gọi khi user submit form
 * @param {string} title - Nội dung task
 */
async function addTask(title) {
  try {
    // Gửi POST request đến create.php
    const response = await fetch(`${API_URL}/create.php`, {
      method: "POST", // Phương thức POST
      headers: {
        "Content-Type": "application/json", // Báo cho PHP biết: dữ liệu gửi lên là JSON
      },
      body: JSON.stringify({ title: title }), // Chuyển object JS → chuỗi JSON
      // JSON.stringify({ title: "Học bài" }) → '{"title":"Học bài"}'
    });

    const data = await response.json();

    if (data.success) {
      // Thêm thành công → load lại danh sách
      loadTasks();
      // Xóa nội dung ô input
      todoInput.value = "";
      hideError();
    } else {
      showError(data.message || "Không thể thêm task.");
    }
  } catch (error) {
    console.error("Lỗi khi thêm task:", error);
    showError("Lỗi kết nối server.");
  }
}

/**
 * Xóa task (DELETE)
 * @param {number} id - ID của task cần xóa
 */
async function deleteTask(id) {
  // Hiển thị hộp thoại xác nhận trước khi xóa
  if (!confirm("Bạn có chắc chắn muốn xóa task này?")) {
    return; // Nếu user bấm "Hủy" → không làm gì
  }

  try {
    const response = await fetch(`${API_URL}/delete.php`, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ id: id }),
    });

    const data = await response.json();

    if (data.success) {
      // Xóa thành công → load lại danh sách
      loadTasks();
    } else {
      showError(data.message || "Không thể xóa task.");
    }
  } catch (error) {
    console.error("Lỗi khi xóa task:", error);
    showError("Lỗi kết nối server.");
  }
}

/**
 * Bắt đầu chế độ sửa task
 * Chuyển text thành ô input
 * @param {number} id - ID của task cần sửa
 */
function startEdit(id) {
  // Tìm phần tử <li> có data-id khớp với id
  const li = document.querySelector(`.todo-item[data-id="${id}"]`);
  if (!li) return;

  // Lấy nội dung text hiện tại
  const textSpan = li.querySelector(".todo-text");
  const currentText = textSpan.textContent;

  // Thay thế nội dung <li> bằng ô input + nút Lưu/Hủy
  const actionsDiv = li.querySelector(".todo-actions");
  textSpan.style.display = "none";
  actionsDiv.style.display = "none";

  // Tạo ô input để sửa
  const editInput = document.createElement("input");
  editInput.type = "text";
  editInput.className = "edit-input";
  editInput.value = currentText;
  editInput.maxLength = 255;

  // Tạo nút Lưu
  const saveBtn = document.createElement("button");
  saveBtn.className = "btn btn-save";
  saveBtn.textContent = "💾 Lưu";
  saveBtn.onclick = function () {
    updateTask(id, editInput.value);
  };

  // Tạo nút Hủy
  const cancelBtn = document.createElement("button");
  cancelBtn.className = "btn btn-cancel";
  cancelBtn.textContent = "❌ Hủy";
  cancelBtn.onclick = function () {
    // Hủy sửa → load lại để khôi phục giao diện
    loadTasks();
  };

  // Tạo div chứa nút Lưu + Hủy
  const editActions = document.createElement("div");
  editActions.className = "todo-actions";
  editActions.appendChild(saveBtn);
  editActions.appendChild(cancelBtn);

  // Chèn ô input và nút vào sau checkbox
  const checkbox = li.querySelector(".todo-checkbox");
  checkbox.after(editInput);
  editInput.after(editActions);

  // Focus vào ô input
  editInput.focus();

  // Bấm Enter cũng trigger Lưu
  editInput.addEventListener("keypress", function (e) {
    if (e.key === "Enter") {
      updateTask(id, editInput.value);
    }
  });
}

/**
 * Cập nhật task (UPDATE - sửa nội dung)
 * @param {number} id - ID của task
 * @param {string} newTitle - Nội dung mới
 */
async function updateTask(id, newTitle) {
  // Kiểm tra nội dung mới có rỗng không
  newTitle = newTitle.trim();
  if (newTitle === "") {
    showError("Nội dung task không được để trống.");
    return;
  }

  try {
    const response = await fetch(`${API_URL}/update.php`, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({
        id: id,
        title: newTitle,
      }),
    });

    const data = await response.json();

    if (data.success) {
      loadTasks();
      hideError();
    } else {
      showError(data.message || "Không thể cập nhật task.");
    }
  } catch (error) {
    console.error("Lỗi khi cập nhật task:", error);
    showError("Lỗi kết nối server.");
  }
}

/**
 * Toggle trạng thái task (pending ↔ completed)
 * @param {number} id - ID của task
 * @param {boolean} isCompleted - true nếu checkbox được checked
 */
async function toggleTask(id, isCompleted) {
  try {
    const response = await fetch(`${API_URL}/update.php`, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({
        id: id,
        status: isCompleted ? "completed" : "pending",
      }),
    });

    const data = await response.json();

    if (data.success) {
      loadTasks();
    } else {
      showError(data.message || "Không thể cập nhật trạng thái.");
    }
  } catch (error) {
    console.error("Lỗi khi toggle task:", error);
    showError("Lỗi kết nối server.");
  }
}

// ===== PHẦN 4: HÀM TIỆN ÍCH =====

/**
 * Hiển thị thông báo lỗi
 * @param {string} message - Nội dung lỗi
 */
function showError(message) {
  errorMessage.textContent = message;
  errorMessage.style.display = "block";

  // Tự ẩn sau 5 giây
  setTimeout(function () {
    hideError();
  }, 5000);
}

/**
 * Ẩn thông báo lỗi
 */
function hideError() {
  errorMessage.style.display = "none";
}

/**
 * Cập nhật thống kê ở footer
 * @param {Array} tasks - Mảng các task
 */
function updateStats(tasks) {
  const total = tasks.length;
  const completed = tasks.filter(function (task) {
    return task.status === "completed";
  }).length;
  const pending = total - completed;

  totalCount.textContent = total;
  completedCount.textContent = completed;
  pendingCount.textContent = pending;
}

/**
 * Escape HTML để chống XSS (Cross-Site Scripting)
 * Nếu user nhập <script>alert('hack')</script> vào ô input,
 * hàm này sẽ chuyển thành text thuần, không chạy được script.
 * @param {string} text - Chuỗi cần escape
 * @returns {string} - Chuỗi đã escape an toàn
 */
function escapeHtml(text) {
  const div = document.createElement("div");
  div.textContent = text;
  return div.innerHTML;
}

// ===== PHẦN 5: KHỞI CHẠY =====

// Gắn sự kiện SUBMIT cho form
// Khi user bấm nút "Thêm" hoặc nhấn Enter
todoForm.addEventListener("submit", function (event) {
  // Ngăn form gửi theo cách mặc định (sẽ reload trang)
  event.preventDefault();

  // Lấy nội dung ô input, trim() để bỏ khoảng trắng đầu/cuối
  const title = todoInput.value.trim();

  // Kiểm tra: nếu rỗng → báo lỗi
  if (title === "") {
    showError("Vui lòng nhập nội dung task.");
    todoInput.focus();
    return;
  }

  // Nếu OK → gọi hàm thêm task
  addTask(title);
});

// Khi trang load xong → tự động đọc danh sách task từ Backend
// DOMContentLoaded: Sự kiện khi HTML đã load xong (chưa cần ảnh/CSS xong)
document.addEventListener("DOMContentLoaded", function () {
  loadTasks();
});
