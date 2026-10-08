import os
import sys

sys.stdout.reconfigure(encoding="utf-8")
from pptx import Presentation
from pptx.util import Inches, Pt
from pptx.dml.color import RGBColor
from pptx.enum.text import PP_ALIGN
from pptx.enum.shapes import MSO_SHAPE

pptx_path = os.path.join(os.path.dirname(os.path.dirname(__file__)), "..", "FixNear_BaoCao_ASM_7Buoc_v2.pptx")
pptx_path = os.path.abspath(pptx_path)

prs = Presentation(pptx_path)
print(f"Original slides count: {len(prs.slides)}")

# Use blank layout (typically layout 6 or last)
blank_layout = prs.slide_layouts[6] if len(prs.slide_layouts) > 6 else prs.slide_layouts[-1]

# Colors
C_DARK = RGBColor(15, 23, 42)       # #0f172a
C_ORANGE = RGBColor(234, 88, 12)    # #ea580c
C_MUTED = RGBColor(100, 116, 139)   # #64748b
C_LIGHT_BG = RGBColor(248, 250, 252)# #f8fafc
C_BORDER = RGBColor(226, 232, 240)  # #e2e8f0
C_GREEN = RGBColor(22, 163, 74)     # #16a34a
C_BLUE = RGBColor(2, 132, 199)      # #0284c7
C_WHITE = RGBColor(255, 255, 255)

def add_header(slide, category_text, title_text):
    # Category / breadcrumb
    cat_box = slide.shapes.add_textbox(Inches(0.6), Inches(0.4), Inches(10.5), Inches(0.4))
    tf = cat_box.text_frame
    tf.word_wrap = True
    p = tf.paragraphs[0]
    p.text = category_text.upper()
    p.font.size = Pt(11)
    p.font.bold = True
    p.font.color.rgb = C_ORANGE
    
    # Title
    title_box = slide.shapes.add_textbox(Inches(0.6), Inches(0.8), Inches(12.13), Inches(0.65))
    tf2 = title_box.text_frame
    tf2.word_wrap = True
    p2 = tf2.paragraphs[0]
    p2.text = title_text
    p2.font.size = Pt(22)
    p2.font.bold = True
    p2.font.color.rgb = C_DARK

def add_footer(slide, current_idx, total_slides):
    ft_box = slide.shapes.add_textbox(Inches(0.6), Inches(7.05), Inches(6.0), Inches(0.3))
    p = ft_box.text_frame.paragraphs[0]
    p.text = "FIXNEAR  ·  PDP104  ·  BÁO CÁO THỰC CHIẾN ASM"
    p.font.size = Pt(9)
    p.font.color.rgb = C_MUTED
    
    pg_box = slide.shapes.add_textbox(Inches(11.23), Inches(7.05), Inches(1.5), Inches(0.3))
    p2 = pg_box.text_frame.paragraphs[0]
    p2.text = f"{current_idx:02d} / {total_slides:02d}"
    p2.alignment = PP_ALIGN.RIGHT
    p2.font.size = Pt(9)
    p2.font.color.rgb = C_MUTED

# ==================== SLIDE 1: MỤC ĐÍCH & MỨC ĐỘ THỰC NGHIỆM ====================
s1 = prs.slides.add_slide(blank_layout)
add_header(s1, "I. TỔNG QUAN  ·  MỤC ĐÍCH & MỨC ĐỘ THỰC NGHIỆM", "Mục Đích Dự Án & Mức Độ Thực Nghiệm Thực Tế")

# Left Box: Mục đích & Nỗi đau thị trường
box_left = s1.shapes.add_shape(MSO_SHAPE.ROUNDED_RECTANGLE, Inches(0.6), Inches(1.65), Inches(5.8), Inches(5.15))
box_left.fill.solid()
box_left.fill.fore_color.rgb = RGBColor(254, 242, 242) # light red/pink
box_left.line.color.rgb = RGBColor(252, 165, 165)
tf = box_left.text_frame
tf.word_wrap = True
p = tf.paragraphs[0]
p.text = "🎯 MỤC ĐÍCH DỰ ÁN & BÀI TOÁN CẦN GIẢI QUYẾT"
p.font.bold = True
p.font.size = Pt(13)
p.font.color.rgb = RGBColor(185, 28, 28)

points_left = [
    ("Nỗi sợ 'luộc đồ' & tráo linh kiện:", "Sinh viên và người dùng công nghệ luôn bất an khi gửi máy lại tiệm, sợ bị tráo màn hình zin, đổi pin kém chất lượng."),
    ("Mù mờ về giá cả & bị chặt chém:", "Không có chuẩn giá tham khảo, cùng một lỗi thay bàn phím hoặc sấy nước mỗi tiệm báo một giá chênh lệch hàng triệu đồng."),
    ("Khó khăn xác định tay nghề thợ:", "Không biết tiệm nào chuyên sửa máy tính Windows, tiệm nào chuyên MacBook hay ép kính điện thoại chuẩn xác."),
    ("Sứ mệnh của FixNear:", "Xây dựng nền tảng số hóa đầu tiên tại Miền Nam minh bạch hóa 100% bảng giá, kiểm định tay nghề thợ và cam kết ký tên linh kiện.")
]
for title, desc in points_left:
    p_t = tf.add_paragraph()
    p_t.text = f"• {title}"
    p_t.font.bold = True
    p_t.font.size = Pt(11)
    p_t.font.color.rgb = C_DARK
    p_d = tf.add_paragraph()
    p_d.text = f"   {desc}"
    p_d.font.size = Pt(10)
    p_d.font.color.rgb = RGBColor(51, 65, 85)

# Right Box: Mức độ thực nghiệm số liệu thật
box_right = s1.shapes.add_shape(MSO_SHAPE.ROUNDED_RECTANGLE, Inches(6.8), Inches(1.65), Inches(5.9), Inches(5.15))
box_right.fill.solid()
box_right.fill.fore_color.rgb = RGBColor(240, 253, 244) # light green
box_right.line.color.rgb = RGBColor(134, 239, 172)
tf2 = box_right.text_frame
tf2.word_wrap = True
p2 = tf2.paragraphs[0]
p2.text = "📊 MỨC ĐỘ THỰC NGHIỆM VÀ SỐ HÓA THỰC TẾ"
p2.font.bold = True
p2.font.size = Pt(13)
p2.font.color.rgb = C_GREEN

points_right = [
    ("Khảo sát & số hóa 151 CỬA HÀNG THẬT 100%:", "Thu thập dữ liệu thực địa phủ khắp 28 quận/huyện TP.HCM và các tỉnh lân cận (Bình Dương, Đồng Nai, Vũng Tàu, Cần Thơ)."),
    ("Chuẩn hóa 18 danh mục dịch vụ quốc tế:", "Xây dựng bảng giá thị trường chuẩn theo mô hình RepairBookings (màn hình, pin, bàn phím, bản lề, SSD, VGA...)."),
    ("Dữ liệu đánh giá Google Reviews thật:", "Toàn bộ điểm rating từ 4.5 - 4.9 sao và hàng trăm nhận xét thực tế của khách hàng được tích hợp minh bạch."),
    ("Kiểm định 3 Tiêu Chuẩn Vàng bắt buộc:", "100% tiệm đối tác cam kết: (1) Cho ngồi xem sửa trực tiếp; (2) Ký tên lên linh kiện; (3) Giảm 15% cho sinh viên FPT & các trường.")
]
for title, desc in points_right:
    p_t = tf2.add_paragraph()
    p_t.text = f"✓ {title}"
    p_t.font.bold = True
    p_t.font.size = Pt(11)
    p_t.font.color.rgb = C_GREEN
    p_d = tf2.add_paragraph()
    p_d.text = f"   {desc}"
    p_d.font.size = Pt(10)
    p_d.font.color.rgb = RGBColor(51, 65, 85)

# ==================== SLIDE 2: 4 MODULES NAVIGATION TRỌNG TÂM ====================
s2 = prs.slides.add_slide(blank_layout)
add_header(s2, "II. CẤU TRÚC HỆ THỐNG  ·  4 PHÂN HỆ NAVIGATION", "Cách Vận Hành Website Qua 4 Module Trọng Tâm")

modules = [
    ("MODULE 1", "🔍 Trang Chủ & Tìm Kiếm Bản Đồ", "index.php & search.php",
     ["• Wizard 2 bước chuẩn RepairBookings: Chọn dòng máy -> Chọn pan bệnh.",
      "• Định vị GPS vệ tinh tính khoảng cách km đến từng tiệm gần nhất.",
      "• Bản đồ số tương tác Leaflet hiển thị trực quan các cửa hàng xung quanh.",
      "• Bộ lọc đa chiều: theo khoảng giá, bán kính 1km-20km, loại thiết bị."]),
    
    ("MODULE 2", "📝 Gửi Thông Tin Thiết Bị Báo Giá", "request_repair.php",
     ["• Khách hàng nhập model máy, triệu chứng hỏng hóc & tải ảnh chụp.",
      "• Hệ thống phân tích pan bệnh và đưa ra khung giá chuẩn thị trường tức thì.",
      "• Tự động đề xuất top 4 cửa hàng gần nhất chuyên trị đúng lỗi đó.",
      "• Lưu trữ bền vững vào CSDL và cập nhật trạng thái xử lý cho khách."]),

    ("MODULE 3", "⭐ Thợ & Tiệm Uy Tín Minh Bạch", "shops.php",
     ["• Hồ sơ năng lực thợ: số năm kinh nghiệm, các pan bệnh sở trường.",
      "• Cam kết 3 tiêu chuẩn: Xem sửa trực tiếp, Ký tên linh kiện, Bảo hành 6-36T.",
      "• Lọc theo tay nghề chuyên môn: Chuyên iPhone, Laptop gaming, MacBook.",
      "• Tích hợp nút xem bảng giá chi tiết và chỉ đường Google Maps 1 chạm."]),

    ("MODULE 4", "📞 Liên Hệ & Đồng Hành Dự Án", "contact.php",
     ["• Tiếp nhận phản ánh, khiếu nại của khách hàng nếu tiệm có dấu hiệu sai phạm.",
      "• Cổng đăng ký dành cho các thợ sửa/cửa hàng muốn gia nhập mạng lưới.",
      "• Hotline kỹ thuật 24/7 & kênh Zalo tư vấn xử lý sự cố thiết bị khẩn cấp.",
      "• Kênh kết nối nhà trường, đối tác học thuật và tài trợ sinh viên."])
]

col_w = Inches(2.85)
col_gap = Inches(0.24)
left_start = Inches(0.6)

for idx, (mod_tag, mod_name, mod_file, mod_items) in enumerate(modules):
    cur_left = left_start + idx * (col_w + col_gap)
    card = s2.shapes.add_shape(MSO_SHAPE.ROUNDED_RECTANGLE, cur_left, Inches(1.65), col_w, Inches(5.15))
    card.fill.solid()
    card.fill.fore_color.rgb = RGBColor(255, 255, 255)
    card.line.color.rgb = C_ORANGE if idx == 0 else C_BORDER
    card.line.width = Pt(2 if idx == 0 else 1)
    
    tf = card.text_frame
    tf.word_wrap = True
    
    # Tag
    p_tag = tf.paragraphs[0]
    p_tag.text = mod_tag
    p_tag.font.bold = True
    p_tag.font.size = Pt(10)
    p_tag.font.color.rgb = C_ORANGE
    
    # Name
    p_nm = tf.add_paragraph()
    p_nm.text = mod_name
    p_nm.font.bold = True
    p_nm.font.size = Pt(12)
    p_nm.font.color.rgb = C_DARK
    
    # File
    p_fl = tf.add_paragraph()
    p_fl.text = f"URL: {mod_file}"
    p_fl.font.size = Pt(9)
    p_fl.font.color.rgb = C_BLUE
    
    tf.add_paragraph().text = "" # spacer
    
    # Items
    for item in mod_items:
        p_it = tf.add_paragraph()
        p_it.text = item
        p_it.font.size = Pt(9.5)
        p_it.font.color.rgb = RGBColor(51, 65, 85)

# ==================== SLIDE 3: TRANG TRẢ KẾT QUẢ CUỐI CÙNG ====================
s3 = prs.slides.add_slide(blank_layout)
add_header(s3, "III. GIẢI PHÁP NGHIỆP VỤ  ·  KẾT QUẢ CUỐI CÙNG", "Trang Trả Kết Quả Cuối Cùng Cho Người Dùng")

# Left Column: Câu hỏi kiểm tra & Khẳng định
left_c = s3.shapes.add_shape(MSO_SHAPE.ROUNDED_RECTANGLE, Inches(0.6), Inches(1.65), Inches(5.0), Inches(5.15))
left_c.fill.solid()
left_c.fill.fore_color.rgb = RGBColor(15, 23, 42) # Dark navy
left_c.line.color.rgb = C_ORANGE
tf = left_c.text_frame
tf.word_wrap = True

p = tf.paragraphs[0]
p.text = "❓ TRANG TRẢ KẾT QUẢ CUỐI CÙNG ĐÃ CÓ CHƯA?"
p.font.bold = True
p.font.size = Pt(12)
p.font.color.rgb = RGBColor(251, 146, 60)

p_ans = tf.add_paragraph()
p_ans.text = "KHẲNG ĐỊNH: ĐÃ CÓ & HOÀN THIỆN 100%!"
p_ans.font.bold = True
p_ans.font.size = Pt(15)
p_ans.font.color.rgb = RGBColor(34, 197, 94)

p_sub = tf.add_paragraph()
p_sub.text = "Đó chính là Trang Chi Tiết Cửa Hàng (shop_detail.php) kết hợp Bộ Lọc Bản Đồ (search.php):"
p_sub.font.size = Pt(10.5)
p_sub.font.color.rgb = RGBColor(203, 213, 225)

tf.add_paragraph().text = ""

results = [
    ("1. Tiệm nào gần tôi nhất?", "Hệ thống định vị GPS tự động đo chính xác khoảng cách (vd: 900m, 1.6km) và xếp tiệm gần nhất lên đầu."),
    ("2. Sửa lỗi này hết bao nhiêu tiền?", "Niêm yết bảng giá chi tiết từng dịch vụ, minh bạch giá linh kiện zin và công thợ, không phát sinh."),
    ("3. Sửa bao lâu thì lấy được máy?", "Hiển thị thời gian xử lý cụ thể: 15-30 phút (lấy liền) hoặc 1-2 ngày đối với pan bệnh nặng."),
    ("4. Chế độ bảo hành ra sao?", "Ghi rõ thời hạn bảo hành từ 6 đến 36 tháng chính hãng cho từng linh kiện."),
    ("5. Có sợ bị tráo đổi đồ không?", "Cam kết cho khách ngồi xem trực tiếp tại bàn + ký tên niêm phong lên toàn bộ linh kiện máy.")
]

for q, a in results:
    pq = tf.add_paragraph()
    pq.text = q
    pq.font.bold = True
    pq.font.size = Pt(10.5)
    pq.font.color.rgb = RGBColor(251, 146, 60)
    pa = tf.add_paragraph()
    pa.text = f"→ {a}"
    pa.font.size = Pt(9.5)
    pa.font.color.rgb = RGBColor(241, 245, 249)

# Right Column: Chi tiết các tính năng trên trang kết quả cuối cùng
right_c = s3.shapes.add_shape(MSO_SHAPE.ROUNDED_RECTANGLE, Inches(5.8), Inches(1.65), Inches(6.9), Inches(5.15))
right_c.fill.solid()
right_c.fill.fore_color.rgb = RGBColor(255, 255, 255)
right_c.line.color.rgb = C_BORDER
tf2 = right_c.text_frame
tf2.word_wrap = True

p_r = tf2.paragraphs[0]
p_r.text = "📱 CÁC TÁC VỤ THỰC CHIẾN TRÊN TRANG KẾT QUẢ CUỐI CÙNG"
p_r.font.bold = True
p_r.font.size = Pt(13)
p_r.font.color.rgb = C_DARK

features = [
    ("Bảng giá ma trận theo model máy:", "Khách hàng tra đúng dòng máy của mình (vd: Dell Inspiron 5510, iPhone 13 Pro) và xem đúng giá thay màn hình, thay pin, sửa nguồn."),
    ("Nút Gọi Hotline & Chỉ Đường 1 Chạm:", "Bấm 'Chỉ đường' tự động mở Google Maps dẫn đường chính xác từ vị trí khách đến cửa hàng."),
    ("Đánh giá sao thật & Đọc review khách trước:", "Khách hàng đăng nhập có thể viết nhận xét thực tế, chia sẻ trải nghiệm để cộng đồng cùng đánh giá."),
    ("Cơ chế Khiếu Nại Báo Cáo Sai Lệch (Report):", "Nếu tiệm báo giá cao hơn trên web hoặc phục vụ kém, khách bấm 'Báo cáo thông tin sai' để Admin xử lý và gỡ nhãn uy tín."),
    ("Nhận diện Ưu đãi 15% Sinh Viên:", "Badge giảm giá học sinh sinh viên hiển thị rõ ràng, giúp tiết kiệm chi phí tối đa.")
]

for t, d in features:
    pt = tf2.add_paragraph()
    pt.text = f"★ {t}"
    pt.font.bold = True
    pt.font.size = Pt(11)
    pt.font.color.rgb = C_ORANGE
    pd = tf2.add_paragraph()
    pd.text = f"   {d}"
    pd.font.size = Pt(10)
    pd.font.color.rgb = RGBColor(51, 65, 85)

# ==================== SLIDE 4: CÔNG NGHỆ VẬN HÀNH ĐỘT PHÁ ====================
s4 = prs.slides.add_slide(blank_layout)
add_header(s4, "IV. KIẾN TRÚC KỸ THUẬT  ·  CÔNG NGHỆ VẬN HÀNH", "Kiến Trúc Công Nghệ & Các Tính Năng Đột Phá")

tech_boxes = [
    ("DUAL-ENGINE DATABASE", "Cơ Sở Dữ Liệu Kép Bền Vững 100%",
     ["• Chạy trên MySQL PDO (XAMPP) với bảng quan hệ chuẩn hóa 3NF.",
      "• Tự động fallback sang JSON File DB nếu MySQL tắt -> Web không bao giờ lỗi!",
      "• Lưu trữ an toàn: shops, users, reviews, bookings, wrong_info_reports."]),
    
    ("ĐỊNH VỊ GPS THÔNG MINH", "Smart Location Check Không Gây Phiền",
     ["• Tự động kiểm tra: Đã bật vị trí rồi -> Tuyệt đối không hỏi lại.",
      "• Chưa bật vị trí -> Mời người dùng chọn quận hoặc kích hoạt GPS nhẹ nhàng.",
      "• Lưu trữ vĩnh viễn vào localStorage & Cookie, URL luôn sạch sẽ."]),

    ("BANNER QUẢNG CÁO 3S", "Popup Tiếp Thị Tự Động Thông Minh",
     ["• Tự động xuất hiện sau 3 giây khi truy cập website.",
      "• Thiết kế đồ họa 3D hiện đại: Quảng bá chiến dịch Giảm 15% Học sinh - Sinh viên.",
      "• Điều hướng 1 chạm đến trang gửi báo giá thiết bị hoặc xem danh sách tiệm."]),

    ("TRỢ LÝ AI KỸ THUẬT VIÊN", "Chatbot Hỗ Trợ Chẩn Đoán Pan Bệnh 24/7",
     ["• Trả lời tức thì các câu hỏi về giá sửa, thời gian xử lý, cách cứu máy rơi nước.",
      "• Hướng dẫn quy trình ký tên linh kiện để tránh bị tráo đồ.",
      "• Hỗ trợ người dùng tra cứu cửa hàng gần nhất theo quận huyện."])
]

for idx, (tag, title, items) in enumerate(tech_boxes):
    r = idx // 2
    c = idx % 2
    l = Inches(0.6) if c == 0 else Inches(6.8)
    t = Inches(1.65) if r == 0 else Inches(4.3)
    
    b = s4.shapes.add_shape(MSO_SHAPE.ROUNDED_RECTANGLE, l, t, Inches(5.9), Inches(2.45))
    b.fill.solid()
    b.fill.fore_color.rgb = RGBColor(255, 255, 255)
    b.line.color.rgb = C_BORDER
    tf = b.text_frame
    tf.word_wrap = True
    
    p0 = tf.paragraphs[0]
    p0.text = tag
    p0.font.bold = True
    p0.font.size = Pt(10)
    p0.font.color.rgb = C_ORANGE
    
    p1 = tf.add_paragraph()
    p1.text = title
    p1.font.bold = True
    p1.font.size = Pt(12)
    p1.font.color.rgb = C_DARK
    
    for item in items:
        pi = tf.add_paragraph()
        pi.text = item
        pi.font.size = Pt(9.5)
        pi.font.color.rgb = RGBColor(51, 65, 85)

# ==================== SLIDE 5: BÁO CÁO TIẾN ĐỘ THỰC TẾ ====================
s5 = prs.slides.add_slide(blank_layout)
add_header(s5, "V. KẾ HOẠCH & KIỂM SOÁT  ·  TIẾN ĐỘ THỰC TẾ", "Báo Cáo Tiến Độ Thực Tế Đạt 100% (Actual Progress)")

# Summary banner
banner = s5.shapes.add_shape(MSO_SHAPE.ROUNDED_RECTANGLE, Inches(0.6), Inches(1.65), Inches(12.13), Inches(0.85))
banner.fill.solid()
banner.fill.fore_color.rgb = RGBColor(240, 253, 244)
banner.line.color.rgb = RGBColor(134, 239, 172)
tf_b = banner.text_frame
tf_b.word_wrap = True
p = tf_b.paragraphs[0]
p.text = "🎉 TIẾN ĐỘ THỰC TẾ: HOÀN THÀNH 100% CÁC MỤC TIÊU MVP VÀ VƯỢT TIẾN ĐỘ ĐỀ RA"
p.font.bold = True
p.font.size = Pt(12)
p.font.color.rgb = C_GREEN
p2 = tf_b.add_paragraph()
p2.text = "Dự án không chỉ dừng lại ở bản thiết kế lý thuyết mà đã hoàn thiện mã nguồn, cơ sở dữ liệu và vận hành thực tế 100% trên Localhost & XAMPP."
p2.font.size = Pt(10)
p2.font.color.rgb = RGBColor(22, 101, 52)

# Progress Table
table_shape = s5.shapes.add_table(6, 4, Inches(0.6), Inches(2.7), Inches(12.13), Inches(4.0))
table = table_shape.table

# Column widths
table.columns[0].width = Inches(3.2)
table.columns[1].width = Inches(4.3)
table.columns[2].width = Inches(2.2)
table.columns[3].width = Inches(2.43)

headers = ["HẠNG MỤC CÔNG VIỆC", "KẾT QUẢ THỰC NGHIỆM ĐẠT ĐƯỢC", "TIẾN ĐỘ KẾ HOẠCH", "TIẾN ĐỘ THỰC TẾ"]
for col_idx, h in enumerate(headers):
    cell = table.cell(0, col_idx)
    cell.fill.solid()
    cell.fill.fore_color.rgb = C_DARK
    p = cell.text_frame.paragraphs[0]
    p.text = h
    p.font.bold = True
    p.font.size = Pt(10)
    p.font.color.rgb = C_WHITE
    p.alignment = PP_ALIGN.CENTER

progress_data = [
    ("1. Khảo sát & Số hóa dữ liệu", "151 cửa hàng thật phủ khắp 28 quận huyện TP.HCM & Miền Nam; 18 dịch vụ chuẩn hóa", "Mục tiêu: 10-20 tiệm", "151 Tiệm (Vượt 750%)"),
    ("2. Thiết kế UI/UX & Responsive", "Giao diện cam/xanh hiện đại, chuẩn RepairBookings, tương thích 100% điện thoại & máy tính", "Hoàn thành tuần 2", "ĐÃ XONG 100%"),
    ("3. Trọn bộ 4 Module Navigation", "Module 1 (Tìm kiếm), Module 2 (Gửi báo giá), Module 3 (Thợ uy tín), Module 4 (Liên hệ)", "Mục tiêu 2 module", "ĐỦ 4 MODULE (100%)"),
    ("4. CSDL Dual-Engine & Admin", "MySQL + JSON bền vững; Cổng Admin CRUD tiệm, duyệt báo cáo sai phạm, quản lý bảng giá", "Hoàn thành tuần 3", "ĐÃ XONG 100%"),
    ("5. Tính năng đột phá mở rộng", "GPS thông minh, Popup Banner khuyến mãi 3s, Chatbot AI kỹ thuật viên trực tuyến 24/7", "Tính năng tương lai", "ĐÃ TÍCH HỢP 100%")
]

for row_idx, row in enumerate(progress_data):
    for col_idx, text in enumerate(row):
        cell = table.cell(row_idx + 1, col_idx)
        cell.fill.solid()
        cell.fill.fore_color.rgb = RGBColor(248, 250, 252) if row_idx % 2 == 0 else C_WHITE
        p = cell.text_frame.paragraphs[0]
        p.text = text
        p.font.size = Pt(9.5)
        if col_idx == 0:
            p.font.bold = True
            p.font.color.rgb = C_DARK
        elif col_idx == 3:
            p.font.bold = True
            p.font.color.rgb = C_GREEN
            p.alignment = PP_ALIGN.CENTER
        elif col_idx == 2:
            p.font.color.rgb = C_MUTED
            p.alignment = PP_ALIGN.CENTER
        else:
            p.font.color.rgb = RGBColor(51, 65, 85)

# Update footers for all slides
total = len(prs.slides)
print(f"Total slides after adding: {total}")

for idx, slide in enumerate(prs.slides):
    add_footer(slide, idx + 1, total)

output_path = pptx_path
prs.save(output_path)
print(f"Successfully saved enhanced presentation to {output_path}")
