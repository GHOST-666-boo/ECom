from PIL import Image

def make_white_logo(input_path, output_path):
    img = Image.open(input_path).convert("RGBA")
    data = img.getdata()
    
    new_data = []
    for item in data:
        # item is (r, g, b, a)
        r, g, b, a = item
        
        # If the pixel is not transparent
        if a > 0:
            # We want to detect the dark text. 
            # The text is dark gray/black, so R, G, B are all low and close to each other.
            # The leaves are green (G is high, R and B are lower).
            # The gold dot has high R and G, low B.
            # Let's check if it's a dark color (e.g. r < 120 and g < 120 and b < 120) 
            # and not distinctly green (g - r < 30 and g - b < 30)
            is_green = g > r + 20 and g > b + 20
            is_gold = r > b + 50 and g > b + 50
            
            # If it's a dark pixel and not green or gold, make it white
            if r < 130 and g < 130 and b < 130 and not is_green and not is_gold:
                new_data.append((255, 255, 255, a))
            else:
                new_data.append(item)
        else:
            new_data.append(item)
            
    img.putdata(new_data)
    img.save(output_path, "PNG")
    print(f"White logo saved to {output_path}")

if __name__ == "__main__":
    make_white_logo("../frontend/public/logo.png", "../frontend/public/logo-white.png")
    # Also save to backend public
    make_white_logo("../frontend/public/logo.png", "public/logo-white.png")
