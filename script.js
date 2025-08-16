// Attendance Management System JavaScript

// Global variables and state management
let students = [];
let attendanceRecords = [];
let currentDate = new Date().toISOString().split('T')[0];
let filteredStudents = [];

// Initialize the application when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    initializeApp();
});

// Application initialization
function initializeApp() {
    loadDataFromStorage();
    setupEventListeners();
    showSection('dashboard');
    updateDateTime();
    updateDashboardStats();
    setInterval(updateDateTime, 1000); // Update time every second
}

// Data Management Functions
function loadDataFromStorage() {
    // Load students from localStorage
    const storedStudents = localStorage.getItem('attendanceStudents');
    if (storedStudents) {
        students = JSON.parse(storedStudents);
    } else {
        // Initialize with sample data if no data exists
        students = [
            {
                id: 'STU001',
                name: 'John Doe',
                class: 'Grade 10A',
                email: 'john.doe@school.edu',
                enrollmentDate: '2024-01-15'
            },
            {
                id: 'STU002',
                name: 'Jane Smith',
                class: 'Grade 10A',
                email: 'jane.smith@school.edu',
                enrollmentDate: '2024-01-15'
            },
            {
                id: 'STU003',
                name: 'Mike Johnson',
                class: 'Grade 10B',
                email: 'mike.johnson@school.edu',
                enrollmentDate: '2024-01-16'
            },
            {
                id: 'STU004',
                name: 'Sarah Wilson',
                class: 'Grade 10B',
                email: 'sarah.wilson@school.edu',
                enrollmentDate: '2024-01-16'
            }
        ];
        saveStudentsToStorage();
    }

    // Load attendance records from localStorage
    const storedAttendance = localStorage.getItem('attendanceRecords');
    if (storedAttendance) {
        attendanceRecords = JSON.parse(storedAttendance);
    } else {
        // Initialize with sample attendance data
        generateSampleAttendanceData();
    }

    filteredStudents = [...students];
}

function saveStudentsToStorage() {
    localStorage.setItem('attendanceStudents', JSON.stringify(students));
}

function saveAttendanceToStorage() {
    localStorage.setItem('attendanceRecords', JSON.stringify(attendanceRecords));
}

function generateSampleAttendanceData() {
    // Generate sample attendance data for the last 7 days
    const today = new Date();
    attendanceRecords = [];

    for (let i = 6; i >= 0; i--) {
        const date = new Date(today);
        date.setDate(date.getDate() - i);
        const dateString = date.toISOString().split('T')[0];

        students.forEach(student => {
            // Randomly generate attendance (80% chance of being present)
            const isPresent = Math.random() > 0.2;
            attendanceRecords.push({
                studentId: student.id,
                date: dateString,
                status: isPresent ? 'present' : 'absent',
                timestamp: date.toISOString()
            });
        });
    }

    saveAttendanceToStorage();
}

// Event Listeners
function setupEventListeners() {
    // Add student form submission
    document.getElementById('add-student-form').addEventListener('submit', handleAddStudent);
    
    // Edit student form submission
    document.getElementById('edit-student-form').addEventListener('submit', handleEditStudent);
    
    // Student search functionality
    document.getElementById('student-search').addEventListener('input', handleStudentSearch);
    
    // Modal close events
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('modal')) {
            closeModal(e.target.id);
        }
    });
    
    // Escape key to close modals
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            const activeModal = document.querySelector('.modal.active');
            if (activeModal) {
                closeModal(activeModal.id);
            }
        }
    });
}

// Navigation Functions
function showSection(sectionName) {
    // Hide all sections
    const sections = document.querySelectorAll('.content-section');
    sections.forEach(section => {
        section.classList.remove('active');
    });
    
    // Remove active class from all nav items
    const navItems = document.querySelectorAll('.nav-item');
    navItems.forEach(item => {
        item.classList.remove('active');
    });
    
    // Show selected section
    const targetSection = document.getElementById(`${sectionName}-section`);
    if (targetSection) {
        targetSection.classList.add('active');
    }
    
    // Add active class to current nav item
    const activeNavItem = document.querySelector(`[onclick="showSection('${sectionName}')"]`).parentElement;
    if (activeNavItem) {
        activeNavItem.classList.add('active');
    }
    
    // Update page title
    const titles = {
        dashboard: 'Dashboard',
        students: 'Students',
        attendance: 'Mark Attendance',
        reports: 'Reports'
    };
    
    document.getElementById('page-title').textContent = titles[sectionName] || 'Dashboard';
    
    // Load section-specific content
    switch (sectionName) {
        case 'dashboard':
            updateDashboardStats();
            break;
        case 'students':
            renderStudents();
            break;
        case 'attendance':
            renderAttendanceList();
            break;
        case 'reports':
            setupReportDates();
            break;
    }
}

function toggleSidebar() {
    const sidebar = document.querySelector('.sidebar');
    sidebar.classList.toggle('collapsed');
}

// DateTime Functions
function updateDateTime() {
    const now = new Date();
    
    // Update current date
    const dateOptions = { 
        weekday: 'long', 
        year: 'numeric', 
        month: 'long', 
        day: 'numeric' 
    };
    document.getElementById('current-date').textContent = now.toLocaleDateString('en-US', dateOptions);
    
    // Update current time
    const timeOptions = { 
        hour: '2-digit', 
        minute: '2-digit', 
        second: '2-digit',
        hour12: true 
    };
    document.getElementById('current-time').textContent = now.toLocaleTimeString('en-US', timeOptions);
    
    // Update attendance date
    const attendanceDateElement = document.getElementById('attendance-date');
    if (attendanceDateElement) {
        attendanceDateElement.textContent = now.toLocaleDateString('en-US', dateOptions);
    }
}

// Dashboard Functions
function updateDashboardStats() {
    const totalStudents = students.length;
    const todayAttendance = getTodayAttendance();
    const presentToday = todayAttendance.filter(record => record.status === 'present').length;
    const absentToday = todayAttendance.filter(record => record.status === 'absent').length;
    const attendanceRate = totalStudents > 0 ? Math.round((presentToday / totalStudents) * 100) : 0;
    
    // Update stat cards
    document.getElementById('total-students').textContent = totalStudents;
    document.getElementById('present-today').textContent = presentToday;
    document.getElementById('absent-today').textContent = absentToday;
    document.getElementById('attendance-rate').textContent = `${attendanceRate}%`;
    
    // Update recent attendance
    renderRecentAttendance();
}

function getTodayAttendance() {
    const today = new Date().toISOString().split('T')[0];
    return attendanceRecords.filter(record => record.date === today);
}

function renderRecentAttendance() {
    const recentAttendanceContainer = document.getElementById('recent-attendance');
    const last7Days = [];
    
    // Get last 7 days of attendance data
    for (let i = 6; i >= 0; i--) {
        const date = new Date();
        date.setDate(date.getDate() - i);
        const dateString = date.toISOString().split('T')[0];
        
        const dayAttendance = attendanceRecords.filter(record => record.date === dateString);
        const present = dayAttendance.filter(record => record.status === 'present').length;
        const total = students.length;
        const rate = total > 0 ? Math.round((present / total) * 100) : 0;
        
        last7Days.push({
            date: dateString,
            present,
            total,
            rate
        });
    }
    
    const recentHTML = last7Days.map(day => {
        const rateClass = day.rate >= 90 ? 'rate-high' : day.rate >= 70 ? 'rate-medium' : 'rate-low';
        const formattedDate = new Date(day.date).toLocaleDateString('en-US', { 
            month: 'short', 
            day: 'numeric' 
        });
        
        return `
            <div class="recent-item">
                <div class="recent-info">
                    <div class="recent-date">${formattedDate}</div>
                    <div class="recent-stats">${day.present}/${day.total} students</div>
                </div>
                <div class="recent-rate ${rateClass}">${day.rate}%</div>
            </div>
        `;
    }).join('');
    
    recentAttendanceContainer.innerHTML = recentHTML || '<p class="text-center" style="color: #718096;">No attendance data available</p>';
}

// Student Management Functions
function renderStudents() {
    const studentsGrid = document.getElementById('students-grid');
    
    if (filteredStudents.length === 0) {
        studentsGrid.innerHTML = `
            <div class="text-center" style="grid-column: 1 / -1; padding: 3rem; color: #718096;">
                <i class="fas fa-users" style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.5;"></i>
                <p style="font-size: 1.2rem;">No students found</p>
                <p>Add some students to get started</p>
            </div>
        `;
        return;
    }
    
    const studentsHTML = filteredStudents.map((student, index) => {
        const initials = student.name.split(' ').map(name => name[0]).join('').toUpperCase();
        const recentAttendance = getStudentRecentAttendance(student.id);
        const attendanceRate = calculateStudentAttendanceRate(student.id);
        
        return `
            <div class="student-card">
                <div class="student-header">
                    <div class="student-avatar">${initials}</div>
                    <div class="student-info">
                        <h4>${student.name}</h4>
                        <p>${student.class}</p>
                    </div>
                </div>
                <div class="student-details">
                    <div class="student-detail">
                        <i class="fas fa-id-card"></i>
                        <span>ID: ${student.id}</span>
                    </div>
                    <div class="student-detail">
                        <i class="fas fa-envelope"></i>
                        <span>${student.email}</span>
                    </div>
                    <div class="student-detail">
                        <i class="fas fa-calendar"></i>
                        <span>Enrolled: ${new Date(student.enrollmentDate).toLocaleDateString()}</span>
                    </div>
                    <div class="student-detail">
                        <i class="fas fa-chart-line"></i>
                        <span>Attendance: ${attendanceRate}%</span>
                    </div>
                </div>
                <div class="student-actions">
                    <button class="btn btn-primary btn-sm" onclick="editStudent(${index})">
                        <i class="fas fa-edit"></i>
                        Edit
                    </button>
                    <button class="btn btn-danger btn-sm" onclick="deleteStudent(${index})">
                        <i class="fas fa-trash"></i>
                        Delete
                    </button>
                </div>
            </div>
        `;
    }).join('');
    
    studentsGrid.innerHTML = studentsHTML;
}

function getStudentRecentAttendance(studentId) {
    const last7Days = [];
    for (let i = 6; i >= 0; i--) {
        const date = new Date();
        date.setDate(date.getDate() - i);
        const dateString = date.toISOString().split('T')[0];
        const record = attendanceRecords.find(r => r.studentId === studentId && r.date === dateString);
        last7Days.push(record ? record.status : 'absent');
    }
    return last7Days;
}

function calculateStudentAttendanceRate(studentId) {
    const studentAttendance = attendanceRecords.filter(record => record.studentId === studentId);
    if (studentAttendance.length === 0) return 0;
    
    const presentDays = studentAttendance.filter(record => record.status === 'present').length;
    return Math.round((presentDays / studentAttendance.length) * 100);
}

function handleStudentSearch(event) {
    const searchTerm = event.target.value.toLowerCase();
    filteredStudents = students.filter(student => 
        student.name.toLowerCase().includes(searchTerm) ||
        student.id.toLowerCase().includes(searchTerm) ||
        student.class.toLowerCase().includes(searchTerm) ||
        student.email.toLowerCase().includes(searchTerm)
    );
    renderStudents();
}

function showAddStudentModal() {
    document.getElementById('add-student-modal').classList.add('active');
    // Clear form
    document.getElementById('add-student-form').reset();
}

function editStudent(index) {
    const student = filteredStudents[index];
    const originalIndex = students.findIndex(s => s.id === student.id);
    
    // Populate edit form
    document.getElementById('edit-student-index').value = originalIndex;
    document.getElementById('edit-student-name').value = student.name;
    document.getElementById('edit-student-id').value = student.id;
    document.getElementById('edit-student-class').value = student.class;
    document.getElementById('edit-student-email').value = student.email;
    
    // Show modal
    document.getElementById('edit-student-modal').classList.add('active');
}

function deleteStudent(index) {
    const student = filteredStudents[index];
    if (confirm(`Are you sure you want to delete ${student.name}? This action cannot be undone.`)) {
        const originalIndex = students.findIndex(s => s.id === student.id);
        students.splice(originalIndex, 1);
        
        // Remove all attendance records for this student
        attendanceRecords = attendanceRecords.filter(record => record.studentId !== student.id);
        
        saveStudentsToStorage();
        saveAttendanceToStorage();
        
        // Update filtered students
        filteredStudents = students.filter(s => 
            s.name.toLowerCase().includes(document.getElementById('student-search').value.toLowerCase()) ||
            s.id.toLowerCase().includes(document.getElementById('student-search').value.toLowerCase()) ||
            s.class.toLowerCase().includes(document.getElementById('student-search').value.toLowerCase())
        );
        
        renderStudents();
        updateDashboardStats();
        
        // Show success message
        showNotification('Student deleted successfully', 'success');
    }
}

function handleAddStudent(event) {
    event.preventDefault();
    
    const formData = new FormData(event.target);
    const studentData = {
        id: formData.get('student-id') || generateStudentId(),
        name: formData.get('student-name'),
        class: formData.get('student-class'),
        email: formData.get('student-email'),
        enrollmentDate: new Date().toISOString().split('T')[0]
    };
    
    // Validate student ID uniqueness
    if (students.some(s => s.id === studentData.id)) {
        showNotification('Student ID already exists', 'error');
        return;
    }
    
    students.push(studentData);
    saveStudentsToStorage();
    
    // Update filtered students
    filteredStudents = [...students];
    
    closeModal('add-student-modal');
    renderStudents();
    updateDashboardStats();
    
    showNotification('Student added successfully', 'success');
}

function handleEditStudent(event) {
    event.preventDefault();
    
    const formData = new FormData(event.target);
    const index = parseInt(formData.get('edit-student-index'));
    const studentData = {
        id: formData.get('edit-student-id'),
        name: formData.get('edit-student-name'),
        class: formData.get('edit-student-class'),
        email: formData.get('edit-student-email'),
        enrollmentDate: students[index].enrollmentDate // Keep original enrollment date
    };
    
    // Validate student ID uniqueness (excluding current student)
    if (students.some((s, i) => s.id === studentData.id && i !== index)) {
        showNotification('Student ID already exists', 'error');
        return;
    }
    
    students[index] = studentData;
    saveStudentsToStorage();
    
    // Update filtered students
    filteredStudents = [...students];
    
    closeModal('edit-student-modal');
    renderStudents();
    updateDashboardStats();
    
    showNotification('Student updated successfully', 'success');
}

function generateStudentId() {
    const prefix = 'STU';
    const number = (students.length + 1).toString().padStart(3, '0');
    return `${prefix}${number}`;
}

// Attendance Functions
function renderAttendanceList() {
    const attendanceList = document.getElementById('attendance-list');
    const today = new Date().toISOString().split('T')[0];
    
    if (students.length === 0) {
        attendanceList.innerHTML = `
            <div class="text-center" style="padding: 3rem; color: #718096;">
                <i class="fas fa-clipboard-check" style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.5;"></i>
                <p style="font-size: 1.2rem;">No students to mark attendance</p>
                <p>Add some students first</p>
            </div>
        `;
        return;
    }
    
    const attendanceHTML = students.map(student => {
        const initials = student.name.split(' ').map(name => name[0]).join('').toUpperCase();
        const todayRecord = attendanceRecords.find(record => 
            record.studentId === student.id && record.date === today
        );
        const currentStatus = todayRecord ? todayRecord.status : 'absent';
        
        return `
            <div class="attendance-item">
                <div class="student-attendance-info">
                    <div class="student-attendance-avatar">${initials}</div>
                    <div class="student-attendance-details">
                        <h5>${student.name}</h5>
                        <p>${student.class} • ${student.id}</p>
                    </div>
                </div>
                <div class="attendance-controls">
                    <div class="attendance-toggle">
                        <button class="attendance-option present ${currentStatus === 'present' ? 'active' : ''}" 
                                onclick="markAttendance('${student.id}', 'present')">
                            <i class="fas fa-check"></i> Present
                        </button>
                        <button class="attendance-option absent ${currentStatus === 'absent' ? 'active' : ''}" 
                                onclick="markAttendance('${student.id}', 'absent')">
                            <i class="fas fa-times"></i> Absent
                        </button>
                    </div>
                </div>
            </div>
        `;
    }).join('');
    
    attendanceList.innerHTML = attendanceHTML;
}

function markAttendance(studentId, status) {
    const today = new Date().toISOString().split('T')[0];
    const existingRecordIndex = attendanceRecords.findIndex(record => 
        record.studentId === studentId && record.date === today
    );
    
    const attendanceRecord = {
        studentId: studentId,
        date: today,
        status: status,
        timestamp: new Date().toISOString()
    };
    
    if (existingRecordIndex !== -1) {
        attendanceRecords[existingRecordIndex] = attendanceRecord;
    } else {
        attendanceRecords.push(attendanceRecord);
    }
    
    saveAttendanceToStorage();
    renderAttendanceList();
    updateDashboardStats();
}

function markAllPresent() {
    students.forEach(student => {
        markAttendance(student.id, 'present');
    });
    showNotification('All students marked as present', 'success');
}

function markAllAbsent() {
    students.forEach(student => {
        markAttendance(student.id, 'absent');
    });
    showNotification('All students marked as absent', 'success');
}

function saveAttendance() {
    saveAttendanceToStorage();
    showNotification('Attendance saved successfully', 'success');
}

// Report Functions
function setupReportDates() {
    const today = new Date();
    const weekAgo = new Date(today);
    weekAgo.setDate(weekAgo.getDate() - 7);
    
    document.getElementById('start-date').value = weekAgo.toISOString().split('T')[0];
    document.getElementById('end-date').value = today.toISOString().split('T')[0];
}

function generateReport() {
    const startDate = document.getElementById('start-date').value;
    const endDate = document.getElementById('end-date').value;
    
    if (!startDate || !endDate) {
        showNotification('Please select both start and end dates', 'error');
        return;
    }
    
    if (new Date(startDate) > new Date(endDate)) {
        showNotification('Start date cannot be after end date', 'error');
        return;
    }
    
    const reportData = generateReportData(startDate, endDate);
    renderReport(reportData, startDate, endDate);
}

function generateReportData(startDate, endDate) {
    const start = new Date(startDate);
    const end = new Date(endDate);
    const reportData = {
        totalDays: 0,
        studentReports: []
    };
    
    // Calculate total days
    for (let date = new Date(start); date <= end; date.setDate(date.getDate() + 1)) {
        reportData.totalDays++;
    }
    
    // Generate report for each student
    students.forEach(student => {
        const studentData = {
            student: student,
            presentDays: 0,
            absentDays: 0,
            totalDays: reportData.totalDays,
            attendanceRate: 0,
            dailyRecords: []
        };
        
        for (let date = new Date(start); date <= end; date.setDate(date.getDate() + 1)) {
            const dateString = date.toISOString().split('T')[0];
            const record = attendanceRecords.find(r => 
                r.studentId === student.id && r.date === dateString
            );
            
            const status = record ? record.status : 'absent';
            studentData.dailyRecords.push({
                date: dateString,
                status: status
            });
            
            if (status === 'present') {
                studentData.presentDays++;
            } else {
                studentData.absentDays++;
            }
        }
        
        studentData.attendanceRate = studentData.totalDays > 0 ? 
            Math.round((studentData.presentDays / studentData.totalDays) * 100) : 0;
        
        reportData.studentReports.push(studentData);
    });
    
    return reportData;
}

function renderReport(reportData, startDate, endDate) {
    const reportContent = document.getElementById('report-content');
    
    const overallStats = {
        totalStudents: reportData.studentReports.length,
        averageAttendance: reportData.studentReports.length > 0 ? 
            Math.round(reportData.studentReports.reduce((sum, student) => sum + student.attendanceRate, 0) / reportData.studentReports.length) : 0,
        totalPresentDays: reportData.studentReports.reduce((sum, student) => sum + student.presentDays, 0),
        totalAbsentDays: reportData.studentReports.reduce((sum, student) => sum + student.absentDays, 0)
    };
    
    const reportHTML = `
        <div class="report-header" style="margin-bottom: 2rem; padding-bottom: 1rem; border-bottom: 2px solid #edf2f7;">
            <h3 style="margin-bottom: 1rem; color: #2d3748;">Attendance Report</h3>
            <p style="color: #4a5568; margin-bottom: 0.5rem;">
                <strong>Period:</strong> ${new Date(startDate).toLocaleDateString()} - ${new Date(endDate).toLocaleDateString()}
            </p>
            <p style="color: #4a5568;">
                <strong>Total Days:</strong> ${reportData.totalDays}
            </p>
        </div>
        
        <div class="report-stats" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 2rem;">
            <div style="background: #f7fafc; padding: 1.5rem; border-radius: 12px; text-align: center;">
                <h4 style="color: #4a5568; margin-bottom: 0.5rem;">Total Students</h4>
                <p style="font-size: 2rem; font-weight: 700; color: #2d3748; margin: 0;">${overallStats.totalStudents}</p>
            </div>
            <div style="background: #f7fafc; padding: 1.5rem; border-radius: 12px; text-align: center;">
                <h4 style="color: #4a5568; margin-bottom: 0.5rem;">Average Attendance</h4>
                <p style="font-size: 2rem; font-weight: 700; color: #2d3748; margin: 0;">${overallStats.averageAttendance}%</p>
            </div>
            <div style="background: #f7fafc; padding: 1.5rem; border-radius: 12px; text-align: center;">
                <h4 style="color: #4a5568; margin-bottom: 0.5rem;">Total Present</h4>
                <p style="font-size: 2rem; font-weight: 700; color: #48bb78; margin: 0;">${overallStats.totalPresentDays}</p>
            </div>
            <div style="background: #f7fafc; padding: 1.5rem; border-radius: 12px; text-align: center;">
                <h4 style="color: #4a5568; margin-bottom: 0.5rem;">Total Absent</h4>
                <p style="font-size: 2rem; font-weight: 700; color: #f56565; margin: 0;">${overallStats.totalAbsentDays}</p>
            </div>
        </div>
        
        <div class="student-reports">
            <h4 style="margin-bottom: 1rem; color: #2d3748;">Individual Student Reports</h4>
            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse; background: white; border-radius: 8px; overflow: hidden;">
                    <thead>
                        <tr style="background: #edf2f7;">
                            <th style="padding: 1rem; text-align: left; color: #4a5568; font-weight: 600;">Student</th>
                            <th style="padding: 1rem; text-align: left; color: #4a5568; font-weight: 600;">Class</th>
                            <th style="padding: 1rem; text-align: center; color: #4a5568; font-weight: 600;">Present Days</th>
                            <th style="padding: 1rem; text-align: center; color: #4a5568; font-weight: 600;">Absent Days</th>
                            <th style="padding: 1rem; text-align: center; color: #4a5568; font-weight: 600;">Attendance Rate</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${reportData.studentReports.map(studentReport => {
                            const rateClass = studentReport.attendanceRate >= 90 ? '#48bb78' : 
                                            studentReport.attendanceRate >= 70 ? '#ed8936' : '#f56565';
                            return `
                                <tr style="border-bottom: 1px solid #edf2f7;">
                                    <td style="padding: 1rem; color: #2d3748; font-weight: 500;">${studentReport.student.name}</td>
                                    <td style="padding: 1rem; color: #4a5568;">${studentReport.student.class}</td>
                                    <td style="padding: 1rem; text-align: center; color: #48bb78; font-weight: 600;">${studentReport.presentDays}</td>
                                    <td style="padding: 1rem; text-align: center; color: #f56565; font-weight: 600;">${studentReport.absentDays}</td>
                                    <td style="padding: 1rem; text-align: center; color: ${rateClass}; font-weight: 700;">${studentReport.attendanceRate}%</td>
                                </tr>
                            `;
                        }).join('')}
                    </tbody>
                </table>
            </div>
        </div>
        
        <div style="margin-top: 2rem; text-align: center;">
            <button class="btn btn-primary" onclick="printReport()">
                <i class="fas fa-print"></i>
                Print Report
            </button>
            <button class="btn btn-secondary" onclick="exportReport()" style="margin-left: 1rem;">
                <i class="fas fa-download"></i>
                Export CSV
            </button>
        </div>
    `;
    
    reportContent.innerHTML = reportHTML;
}

function printReport() {
    window.print();
}

function exportReport() {
    const startDate = document.getElementById('start-date').value;
    const endDate = document.getElementById('end-date').value;
    const reportData = generateReportData(startDate, endDate);
    
    let csvContent = "Student Name,Student ID,Class,Present Days,Absent Days,Total Days,Attendance Rate\n";
    
    reportData.studentReports.forEach(studentReport => {
        csvContent += `"${studentReport.student.name}","${studentReport.student.id}","${studentReport.student.class}",${studentReport.presentDays},${studentReport.absentDays},${studentReport.totalDays},${studentReport.attendanceRate}%\n`;
    });
    
    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    const url = URL.createObjectURL(blob);
    link.setAttribute('href', url);
    link.setAttribute('download', `attendance_report_${startDate}_to_${endDate}.csv`);
    link.style.visibility = 'hidden';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    
    showNotification('Report exported successfully', 'success');
}

// Modal Functions
function closeModal(modalId) {
    document.getElementById(modalId).classList.remove('active');
}

// Notification Functions
function showNotification(message, type = 'info') {
    // Remove existing notifications
    const existingNotifications = document.querySelectorAll('.notification');
    existingNotifications.forEach(notification => notification.remove());
    
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.style.cssText = `
        position: fixed;
        top: 2rem;
        right: 2rem;
        background: ${type === 'success' ? '#48bb78' : type === 'error' ? '#f56565' : '#4299e1'};
        color: white;
        padding: 1rem 1.5rem;
        border-radius: 12px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
        z-index: 3000;
        animation: slideInRight 0.3s ease;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        max-width: 400px;
    `;
    
    const icon = type === 'success' ? 'fa-check-circle' : type === 'error' ? 'fa-exclamation-circle' : 'fa-info-circle';
    notification.innerHTML = `
        <i class="fas ${icon}"></i>
        <span>${message}</span>
    `;
    
    document.body.appendChild(notification);
    
    // Auto remove after 3 seconds
    setTimeout(() => {
        notification.style.animation = 'slideOutRight 0.3s ease';
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}

// Add CSS for notification animations
const notificationStyles = document.createElement('style');
notificationStyles.textContent = `
    @keyframes slideInRight {
        from {
            opacity: 0;
            transform: translateX(100%);
        }
        to {
            opacity: 1;
            transform: translateX(0);
        }
    }
    
    @keyframes slideOutRight {
        from {
            opacity: 1;
            transform: translateX(0);
        }
        to {
            opacity: 0;
            transform: translateX(100%);
        }
    }
`;
document.head.appendChild(notificationStyles);

// Mobile responsive menu handling
function handleMobileMenu() {
    if (window.innerWidth <= 768) {
        const sidebar = document.querySelector('.sidebar');
        sidebar.classList.add('mobile');
        
        // Close sidebar when clicking outside
        document.addEventListener('click', function(e) {
            if (!sidebar.contains(e.target) && !e.target.classList.contains('menu-toggle')) {
                sidebar.classList.remove('open');
            }
        });
    }
}

// Update mobile menu on resize
window.addEventListener('resize', handleMobileMenu);

// Initialize mobile menu
handleMobileMenu();

// Enhanced sidebar toggle for mobile
function toggleSidebar() {
    const sidebar = document.querySelector('.sidebar');
    if (window.innerWidth <= 768) {
        sidebar.classList.toggle('open');
    } else {
        sidebar.classList.toggle('collapsed');
    }
}