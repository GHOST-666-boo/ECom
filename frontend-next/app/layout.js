import "./globals.css";
import Providers from "../providers/Providers";

export const metadata = {
  title: "Vriddhi Handicrafts & Jewelry",
  description: "Handcrafted with intention. Connecting creators & collectors.",
};

export default function RootLayout({ children }) {
  return (
    <html lang="en" className="h-full antialiased" suppressHydrationWarning>
      <body className="min-h-full flex flex-col" suppressHydrationWarning>
        <Providers>
          {children}
        </Providers>
      </body>
    </html>
  );
}
